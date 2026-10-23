<?php

namespace App\Admin\Actions\Task;

use App\Models\TaskLog;
use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Grid\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class BatchAudit extends BatchAction
{
    public $name = '批量审核';

    public function handle()
    {
        $state = \request()->get('state');
        $key = \request()->get('_key');

        DB::transaction(function () use ($state,$key) {
            $ids = explode(',',$key);
            $logs = TaskLog::whereIn('id',$ids)->get();
            foreach ($logs  as $log) {
                // 改状态
                $log->state = $state;
                $log->save();

                // 计算收益
                if ($state == TaskLog::SUCCESS) {
                    (new TaskLog())->income($log->user_id,$log->task_id);
                }
            }
        });

        return $this->response()->success('审核成功')->refresh();
    }

    public function form()
    {
        $this->radio('state', '审核结果')->options([
            2 => '通过',
            3 => '拒绝',
        ]);
    }


}
