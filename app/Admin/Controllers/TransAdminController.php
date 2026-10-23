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
use App\Admin\Actions\Tran\Newedit;
use Illuminate\Support\Str;

class TransAdminController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '文案';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Tran());

        $key = \request('key');

        $query = $grid->model()->where('type',2)->where('key','=',$key);

        $grid->paginate(100);
        // 禁用导出
        $grid->disableExport();
        $grid->disableCreateButton();


        $grid->tools(function ($tools)use($key) {
            $tools->append('<a href="/admin/trans-admin/create?key='.$key.'" class="btn btn-sm btn-success" title="新增">
        <i class="fa fa-plus"></i><span class="hidden-xs">&nbsp;&nbsp;新增</span>
    </a>');
        });

        $grid->actions(function ($actions)use($key) {
            $actions->disableView();
            $actions->disableEdit();
            //$actions->disableDelete();
            $actions->add(new Newedit);
        });

        $grid->column('id', __('Id'));
        $grid->column('locale_code', '语言编码');
        $grid->column('key', '标识');
        $grid->column('value', '内容')->display(function ($var){
            if(Str::contains($this->key, '.html')){
                return "富文本编辑查看详情";
            }else{
                return $var;
            }

        });
        $grid->column('desc', '备注');
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
        $key = \request('key');
        $form->display('id', 'ID');
        $form->hidden('type')->value(2);
        $form->text('key', '标识')->value($key);
        if(Str::contains($key, '.html')){
            $form->UEditor('value', __('内容'))->required();
        }else{
            $form->textarea('value', '内容')->required();
        }

        $form->text('desc', '备注');
        $form->select('locale_code', '语言')->options(Locale::getAdminLangs())->required();

        if($form->isCreating()){
            $insert_model = Tran::where('locale_code','=',$form->locale_code)->where('type','=',2)->where('key','=',$key)->first();
            if($insert_model){
                $success = new MessageBag([
                    'title'   => '失败...',
                    'message' => '该文案已存在',
                ]);
                return back()->with(compact('success'));
            }
            $form->setAction('/admin/trans-admin?key='.$key);
        }else{
            $id=request()->route()->parameters()['trans_admin'];
            $form->setAction("/admin/trans-admin/{$id}?key=$key");
            $form->saved(function (Form $form)use($key) {
                return redirect('/admin/trans-admin?key='.$key);
            });
        }


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
