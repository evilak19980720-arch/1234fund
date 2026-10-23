<?php

namespace App\Admin\Actions\Youtube;

use App\Handlers\KeysHandler;
use App\Models\Task;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Import extends RowAction
{
    public $name = '导入';


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

    public function handle(Model $model)
    {
        $type_id = request()->type_id;
        $price = request()->price * 100;
        if($model->imported == 1){
            return $this->response()->error('不能重复导入');
        }
        DB::transaction(function () use ($model, $type_id, $price) {
            $task = new Task();

            $task->type_id = $type_id;
            $task->title = $model->title;
            $task->number = 1000;
            $task->finished = rand(50, 500);
            $task->price = $price;
            $task->content = call_user_func(function ($html) use ($model) {
                preg_match('/href=(\"|\')(.*?)(\"|\')/i', $html, $matches, PREG_OFFSET_CAPTURE);
                return str_replace($matches[2][0], $model->link, $html);
            }, $this->content);
            $task->end = Carbon::now()->addDays(7);
            $task->expire = 6000;
            $task->automatic_audit = 1;
            $task->status = 1;

            $task->save();
            $task->setCache();

            $model->imported = 1;
            $model->save();
        });


        return $this->response()->success('导入成功')->refresh();
    }

    public function form()
    {
        $this->select('type_id', '类型')->options('/admin/api/types')->required();
        $this->text('price', '价格')->required();
    }
}
