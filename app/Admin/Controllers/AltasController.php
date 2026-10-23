<?php

namespace App\Admin\Controllers;

use App\Models\Atlas;
use App\Models\Pet;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Displayers\Actions;
use Illuminate\Support\Facades\Request;
use App\Admin\Extensions\Tools\GridView;

/**
 * 图集
 * @package App\Admin\Controllers
 */
class AltasController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '图集';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Atlas());
        $grid->model()->select('atlases.*')->orderBy('id','asc')
                    ->join('pets','pets.id','atlases.pet_id')
                    ->where('pets.admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0);
        // 禁用导出
        $grid->disableExport();

        $grid->setActionClass(Actions::class);

        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('pet_id', '宠物')->select(call_user_func(function () {
                return Pet::query()->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)->pluck('name','id');
            }));
        });

        $grid->tools(function ($tools) {
            $tools->append(new GridView());
        });

        $grid->column('id', __('Id'));
        $grid->pet()->name('宠物');
        $grid->column('title', '标题');
        $grid->img('图片')->lightbox();

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Atlas());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->select('pet_id', '宠物')->options(call_user_func(function () {
            return Pet::query()->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)->pluck('name','id');
        }))->creationRules(['required', 'unique:atlases,pet_id'])
            ->updateRules(['required', 'unique:atlases,pet_id,{{id}}']);
        $form->text('title', '标题');
        // 多图
        $form->multipleImage('img', '图集')->removable();

        return $form;
    }
}
