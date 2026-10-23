<?php

namespace App\Http\Controllers\Api\v1;

use App\Handlers\KeysHandler;
use App\Handlers\HtmlHandler;
use App\Http\Requests\Api\v1\MyTaskRequest;
use App\Http\Requests\Api\v1\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\Type;
use App\Models\Vip;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class TaskController extends Controller
{

    public function index(TaskRequest $request, Task $task)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $limit = 200;
        $page = $this->paging($page, $limit);

        $user = request()->user();

        $key = sprintf(KeysHandler::TASKS, $request->type_id, $page, $limit);
        $rows = Cache::tags('tasks')->get($key) ?: [];
        if (!Cache::has($key)) {
            $query = $task->query();
            $now = Carbon::now()->format('Y-m-d H:i:s');

            if (!Type::getType($request->type_id)) {
                $this->errorResponse(Response::HTTP_OK, trans('error.20000'), 20000);
            }

            $query->where('type_id', $request->type_id)
                ->whereColumn('finished', '<', 'number')
                ->where('status', '=', 1)
                ->where('end', '>', $now);   // 排除已结束的

            $rows = $query->select('id')->offset($page)->limit($limit)->orderBy('tasks.id', 'desc')->get();
            if ($rows) {
                Cache::tags('tasks')->put($key, $rows, 15);
            }

            /*$tasks = $query->with(['type' => function ($query) {
                $query->select('id', 'name', 'icon');
            }])->select('tasks.id', 'type_id', 'title', 'price', 'number', 'finished', 'tasks.created_at')
                ->offset($page)->limit($limit)->orderBy('tasks.created_at', 'desc')
                ->get();*/
        }
        $tasks = [];
        if ($rows) {
            $model = new Task();
            $types = Type::getType();
            foreach ($rows as $row) {
                $task = $model->getTask($row->id);
                //排除已经领取过的任务
                if (!Task::isReceived($task, $user->id)) {
                    $tasks[] = [
                        'id' => (int)$task['id'],
                        'type_id' => (int)$task['type_id'],
                        'title' => $task['title'],
                        'price' => (int)$task['price'],
                        'number' => (int)$task['number'],
                        'finished' => (int)$task['finished'],
                        'created_at' => Carbon::parse($task['created_at'])->timestamp,
                        'type' => call_user_func(function ($id) use ($types) {
                            foreach ($types as $type) {
                                if ($id == $type->id) {
                                    return [
                                        'id' => $type->id,
                                        'name' => $type->name,
                                        'icon' => sprintf('%s/%s', aliyun_domain(), $type->icon)
                                    ];
                                }
                            }
                        }, $task['type_id'])
                    ];
                }
            }
        }
        return $this->success($tasks);
    }

    // 单任务领取
    public function singleTask(Request $request)
    {
        if (!$request->has('type_id')) {
            return $this->fail(422, 'type_id required');
        }
        $type = Type::getType($request->type_id);
        if (!($type && $type->single)) {
            $this->errorResponse(Response::HTTP_OK, trans('error.20000'), 20000);
        }

        $id = Task::where('type_id', '=', $request->type_id)->where('status', '=', 1)->value('id');
        if (!$id) {
            $this->errorResponse(Response::HTTP_OK, trans('error.20007'), 20007);
        }
        $req = new TaskRequest();
        $req->id = $id;

        return $this->store($req);
    }

    // 多任务
    public function store(TaskRequest $request)
    {
        $user = request()->user();
        $model = new Task();

        $task = $model->getTask($request->id);

        if (!$task) {
            $this->errorResponse(Response::HTTP_OK, trans('error.20001'), 20001);
        }

        if (!$task['status']) {
            $this->errorResponse(Response::HTTP_OK, trans('error.20013'), 20013);
        }

        $type = Type::getType($task['type_id']);

        // 判断重复领取
        $task_log_id = Task::isReceived($task, $user->id);
        if ($task_log_id) {
            $log = TaskLog::getTaskLog($task_log_id);
            if ($log->state != TaskLog::CONDUCT) {
                $this->errorResponse(Response::HTTP_OK, trans('error.20012'), 20012);
            }
            return $this->success([
                'id' => (int)$task['id'],
                'task_log_id' => (int)$task_log_id,
                'price' => (int)$task['price'],
                'title' => $task['title'],
                'content' => HtmlHandler::makeContent($task['content']),
                'icon' => sprintf('%s/%s', aliyun_domain(), $type->icon),
                'is_upload' => $type->is_upload,
                'end' => Carbon::parse($task['end'])->timestamp,
                'now' => Carbon::now()->timestamp,
                'img' => $log->img ? sprintf('%s/%s', aliyun_domain(), $log->img) : '',
                'course' => $type->course ?HtmlHandler::makeContent($type->course): '',
                'state' => $log->state
            ]);
        }

        // 数量判断
        if ($task['finished'] >= $task['number']) {
            $this->errorResponse(Response::HTTP_OK, trans('error.20002'), 20002);
        }
        // 结束判断
        if (Carbon::parse($task['end'])->lt(Carbon::now())) {
            $this->errorResponse(Response::HTTP_OK, trans('error.20004'), 20004);
        }
        // vip等级判断
        $taskVip = Vip::getVip($type->vip_id);
        $userVip = Vip::getVip($user->vip_id);
        if ($taskVip->level > $userVip->level) {
            $this->errorResponse(Response::HTTP_OK, trans('error.20005'), 20005);
        }

        // vip任务数量限制
        if (TaskLog::todayCompleted($user->id, $type->vip->id) >= Vip::getVip($type->vip->id)->quantity) {
            $this->errorResponse(Response::HTTP_OK, trans('error.20009'), 20009);
        }

        // 领取
        $log = $model->receive($task, $user->id, $type->vip);
        if($log === false){
            $this->errorResponse(Response::HTTP_OK, trans('error.20002'), 20002);
        }
        Cache::tags($user->id . 'mytasklist')->flush();

        return $this->success([
            'id' => $task['id'],
            'task_log_id' => $log->id,
            'price' => $task['price'],
            'title' => $task['title'],
            'content' => HtmlHandler::makeContent($task['content']),
            'icon' => sprintf('%s/%s', aliyun_domain(), Type::getType($task['type_id'])->icon),
            'is_upload' => $type->is_upload,
            'end' => Carbon::parse($task['end'])->timestamp,
            'now' => Carbon::now()->timestamp,
            'img' => '',
            'course' => $type->course ?HtmlHandler::makeContent($type->course): '',
            'state' => TaskLog::CONDUCT
        ]);
    }

    // 我的任务
    public function myTask(MyTaskRequest $request)
    {
        $user = $request->user();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $page = $this->paging($page, $limit);

        $query = (new TaskLog())->newQuery();
        $query->where('user_id', '=', $user->id);
        switch ($request->type) {
            case 'conduct':
                $query->where('task_logs.state', '=', TaskLog::CONDUCT);
                break;
            case 'audit':
                $query->where('task_logs.state', '=', TaskLog::EXAMINE);
                break;
            case 'complete':
                $query->where('task_logs.state', '=', TaskLog::SUCCESS);
                break;
            case 'fail':
                $query->whereIn('task_logs.state', [TaskLog::FAIL, TaskLog::OVERTIME, TaskLog::GIVE_UP]);
                break;
        }

        $key = sprintf(KeysHandler::MY_TASK, $user->id, $request->type, $page);
        if (!Cache::tags($user->id . 'mytasklist')->has($key)) {
            $tasks = $query->join('tasks', 'tasks.id', '=', 'task_logs.task_id')
                ->join('types', 'tasks.type_id', '=', 'types.id')
                ->select('task_logs.id', 'icon', 'task_id', 'title', 'price', 'task_logs.state', 'task_logs.created_at', 'task_logs.updated_at')
                ->offset($page)->limit($limit)->orderBy('created_at', 'desc')
                ->get();
            Cache::tags($user->id . 'mytasklist')->put($key, $tasks, 60);
        }

        return $this->success(TaskResource::collection(Cache::tags($user->id . 'mytasklist')->get($key)));
    }

    public function submit(TaskRequest $request)
    {
        $user = $request->user();

        $log = TaskLog::getTaskLog($request->id);
        if (!$log) {
            $this->errorResponse(Response::HTTP_OK, trans('error.20001'), 20001);
        }

        $this->authorize('update', $log);

        if ($log->state != TaskLog::CONDUCT) {
            $this->errorResponse(Response::HTTP_OK, trans('error.20008'), 20008);
        }

        $task = (new Task())->getTask($log->task_id);
        // 判断任务过期
        if (Carbon::parse($task['end'])->lt(Carbon::now())) {
            $this->errorResponse(Response::HTTP_OK, trans('error.20004'), 20004);
        }

        $log->update([
            'img' => $request->img,
            'state' => TaskLog::EXAMINE
        ]);

        Cache::tags($user->id . 'mytasklist')->flush();

        return $this->success();
    }

    public function show(Request $request)
    {
        $log = TaskLog::with(['task' => function ($query) {
            $query->select('id', 'type_id', 'title', 'content', 'price', 'end');
        }])->select('id', 'task_id', 'img', 'state')->find($request->id);

        $type = Type::getType($log->task->type_id);

        $data = [
            'id' => $log->task->id,
            'task_log_id' => $log->id,
            'price' => $log->task->price,
            'title' => $log->task->title,
            'content' =>  HtmlHandler::makeContent($log->task->content),
            'icon' => $type->icon ? sprintf('%s/%s', aliyun_domain(), $type->icon) : '',
            'is_upload' => $type->is_upload,
            'log_state' => $log->state,
            'end' => Carbon::parse($log->task->end)->timestamp,
            'now' => Carbon::now()->timestamp,
            'img' => $log->img ? sprintf('%s/%s', aliyun_domain(), $log->img) : '',
            'course' => $type->course ?HtmlHandler::makeContent($type->course): '',
            'state' => $log->state
        ];

        return $this->success($data);
    }
}
