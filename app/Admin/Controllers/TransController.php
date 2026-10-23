<?php

namespace App\Admin\Controllers;

use App\Models\Tran;
use App\Models\Locale;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Jobs\GoogleTrans;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\MessageBag;
use App\Admin\Actions\Tran\Tranimport;

class TransController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '翻译管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Tran());

        $query = $grid->model()->whereIn('type',[0,1]);
        $grid->paginate(50);
        // 禁用导出
        $grid->disableExport();
        // 筛选
        $grid->quickSearch('key');
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('type', '位置')->select([0=>'客户端',1=>'服务端']);
            $filter->equal('locale_code', '语言编码')->select(Locale::getAdminLangs());
            $filter->like('key', '标识');
            $filter->like('value', '内容');
            $filter->where(function ($query) {
                if ($this->input == 1)
                    $query->whereNull('value');
                else if ($this->input == 2)
                    $query->whereNotNull('value');
            }, '内容为空')->radio([
                '' => '不作选择',
                1 => '为空',
                2 => '不为空',
            ]);
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
            //$actions->disableDelete();
        });

        $grid->column('id', __('Id'));
        $grid->column('locale_code', '语言编码');
        $grid->column('type', '类型')->display(function ($var){
            return Tran::TYPE[$var];
        });
        $grid->column('key', '标识');
        $grid->column('value', '内容');
        $grid->column('desc', '备注');


        // 将导入操作加入到表格的工具条中
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new Tranimport());
        });

        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Tran());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->select("type",'类型')->required()->options([0 => '客户端', 1 => '服务端']);
        $form->text('key', '标识')->required();
        $form->textarea('value', '内容')->required();
        $form->text('desc', '备注');
        $form->select('locale_code', '语言')->options(Locale::getAdminLangs())->required();
        //$form->switch('autotrans', '自动翻译');
        $form->radio('autotrans', '自动翻译')
            ->options([
                1 => '是',
                0 => '否',
            ])->default(1);

        $form->ignore(['autotrans']);

        $form->saving(function (Form $form) {
            $autotrans = \request('autotrans');
            if($autotrans == 1){
                $type = $form->type;
                $key = $form->key;
                $value = $form->value;
                $desc = $form->desc;
                GoogleTrans::dispatch($type, $key, $value, $desc)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                //app(Tran::class)->tran($type, $key, $value, $desc);
                $success = new MessageBag([
                    'title'   => '提示...',
                    'message' => '插入成功,稍后刷新查看',
                ]);
                return back()->with(compact('success'));
            }
            if($form->isCreating()){
                $insert_model = Tran::where('locale_code','=',$form->locale_code)->where('type','=',$form->type)->where('key','=',$form->key)->first();
                if($insert_model){
                    $success = new MessageBag([
                        'title'   => '失败...',
                        'message' => '该文案已存在',
                    ]);
                    return back()->with(compact('success'));
                }
            }

        });

        return $form;
    }



    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Tran::findOrFail($id));

        $show->field('id', __('Id'));

        return $show;
    }

}
