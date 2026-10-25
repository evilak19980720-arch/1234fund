<?php

namespace App\Http\Controllers\Api\v1;


use App\Handlers\KeysHandler;
use App\Http\Requests\Api\v1\FriendRequest;
use App\Http\Resources\FriendResource;
use App\Models\Friend;
use App\Jobs\Friend as FriendJob;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 朋友圈
 * @package App\Http\Controllers\Api\v1
 */
class FriendController extends Controller
{
    public function index(FriendRequest $request)
    {
        // 公开接口：游客为 null，带 token 时识别登录用户
        $user = auth('api')->user();
        $type = $request->get('type', 0);
        $page = (int) $request->get('page', 1);
        $limit = min(50, (int) $request->get('limit', 10));

        // 「我的」只给登录用户看
        if($type == 1 && !$user){
            return $this->success([]);
        }

        // 「我的」列表按用户单独缓存，不能和全部列表共用缓存
        $cacheKey = sprintf(KeysHandler::FIREND, $page, $limit) . ($type == 1 ? ':mine:' . $user->id : '');
        $list = Cache::remember($cacheKey, KeysHandler::FIREND_Expire,function () use($user, $page, $limit,$type){
            $page = $this->paging($page, $limit);
            $query = Friend::query()->with(['imgs', 'user' => function ($query){
                $query->with(['vip' =>function($query){
                    $query->select('id', 'name');
                },'countries'])
                    ->select('id', 'name','vip_id','country' ,'whatsapp','telegram','hidephone','whatsapp_hide','telegram_hide');
            }])
                ->select('id', 'user_id', 'content', 'up','times', 'created_at')
                ->orWhere('ok', '=', 1);
            //只查询自己所属代理的用户的圈子
            /*
            $query->whereHas('user',function($sql) use ($user) {
                $sql->where('admin_user_id','=',$user->admin_user_id);
            });
            */
            if($type == 1){
                $query->where('user_id', $user->id);
            }
            $list = $query->offset($page)->limit($limit)->orderBy('id', 'desc')->get();
            return $list;
        });

        return $this->success(FriendResource::collection($list));
    }

    public function store(FriendRequest $request)
    {
        $user = $request->user();

        if($user->hierarchy_id != 5){
            $orders = Order::query()
                ->where('type', '=', Order::WITHDRAWAL)
                ->where('user_id', '=', $user->id)
                ->where('state','=',1)
                ->count();
            if($orders == 0){
                $this->errorResponse(Response::HTTP_OK, transtable('10051'), 10051);
            }
        }



        $friend = DB::transaction(function () use ($user, $request) {
            $arr = $request->imgs;
            $friend = false;
            if (!empty($arr)) {
                foreach ($arr as $img) {
                    if(!empty($img)){
                        $friend = $user->friends()->create([
                            'content' => $request->get('content')
                        ]);
                        break;
                    }
                }
                foreach ($arr as $img) {
                    if(!empty($img)){
                        $friend->imgs()->create([
                            'img' => $img
                        ]);
                    }
                }

            }
            return $friend;
        });
        FriendJob::dispatch($friend->id)->delay(Carbon::now()->addMinutes(3))->onQueue(env('REDIS_QUEUE', 'default'));
        return $this->success();
    }

    public function update(Friend $friend, FriendRequest $request)
    {
        if (!$friend) {
            $this->errorResponse(Response::HTTP_OK, transtable('404'), 404);
        }

        $user = $request->user();
        if ($friend->up) {
            $arr = explode(',', $friend->up);
            if (in_array($user->id, $arr)) {
                $arr = array_filter($arr, function ($var) use ($user) {
                    return $user->id != $var;
                });
            } else {
                array_push($arr, $user->id);
            }
        } else {
            $arr[] = $user->id;
        }

        $friend->up = $arr ? implode(',', $arr) : '';
        $friend->save();
        //return $this->success(count($arr)+$friend->times);
        return $this->success(); //修改为不返回点赞数量
    }

    public function delete(Friend $friend, FriendRequest $request)
    {
        //$this->authorize('delete', $friend);
        //$friend->delete();
        return $this->success();
    }
}
