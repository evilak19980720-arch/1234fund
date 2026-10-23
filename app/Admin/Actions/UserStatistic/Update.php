<?php

namespace App\Admin\Actions\UserStatistic;

use App\Jobs\UpdateUserStatisticJob;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class Update extends BatchAction
{
    public $name = '批量更新';

    public function handle(Collection $collection)
    {
        foreach ($collection as $model) {
            UpdateUserStatisticJob::dispatch($model)->onQueue(env('REDIS_QUEUE', 'default'));
        }
        return $this->response()->success('提交成功，等待异步更新')->refresh();
    }

    public function form()
    {
        $this->radio('update', '更新')->options([
            1 => '更新',
        ])->required();
    }

}
