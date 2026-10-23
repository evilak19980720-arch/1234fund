<?php

namespace App\Admin\Actions\Youtube;

use App\Models\Task;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BatchImport extends BatchAction
{
    public $name = '批量导入';

    private $content = <<<STR
<p>
    <br/>
</p>
<ol class=" list-paddingleft-2">
    <li>
        <p>
            <strong>Open this link&nbsp;</strong><span id="youtubuImportBox"><a href="https://www.youtube.com/watch?v=mMgzX692uP8" target="_blank" title="https://www.youtube.com/watch?v=mMgzX692uP8"><strong>https://www.youtube.com/watch?v=mMgzX692uP8</strong></a></span>
        </p>
    </li>
    <li>
        <p>
            <strong>Like The video and Follow The Youtuber</strong>
        </p>
    </li>
    <li>
        <p>
            <strong>Upload your like proof and complete the task</strong>
        </p>
    </li>
</ol>
<p>
    <br/>
</p>
STR;

    public function handle(Collection $collection)
    {
        $type_id = request()->type_id;
        $price = request()->price * 100;

        foreach ($collection as $model) {
            if($model->imported == 0){
                DB::transaction(function () use ($model, $type_id, $price) {
                    $task = new Task();

                    $task->type_id = $type_id;
                    $task->title = $model->title;
                    $task->number = 1000;
                    $task->finished = rand(50, 500);
                    $task->price = $price;
                    $task->content = call_user_func(function () use ($model) {
                        preg_match('/href=(\"|\')(.*?)(\"|\')/i', $this->content, $matches, PREG_OFFSET_CAPTURE);
                        return str_replace($matches[2][0], $model->link, $this->content);
                    });
                    $task->end = Carbon::now()->addDays(7);
                    $task->expire = 6000;
                    $task->automatic_audit = 1;
                    $task->status = 1;

                    $task->save();
                    $task->setCache();

                    // 已导出
                    $model->imported = 1;
                    $model->save();
                });
            }

        }

        return $this->response()->success('Success message...')->refresh();
    }

    public function form()
    {
        $this->select('type_id', '类型')->options('/admin/api/types')->required();
        $this->text('price', '价格')->required();
    }
}
