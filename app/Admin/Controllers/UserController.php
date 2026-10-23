<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\User\BankAction;
use App\Admin\Actions\User\Deduction;
use App\Admin\Actions\User\JumpTo;
use App\Admin\Actions\User\Recharge;
use App\Admin\Actions\User\{AdminAddFundAmount, AdminSubFundAmount};
use App\Models\Account;
use App\Models\Agent;
use App\Models\Bank;
use App\Models\Configuration;
use App\Models\Country;
use App\Models\Hierarchy;
use App\Models\User;
use danielme85\Geoip2\Facade\Reader;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Admin\Actions\User\ResetPassword;
use Encore\Admin\Widgets\Tab;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use function Clue\StreamFilter\fun;

class UserController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '会员';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        $data = \request()->all();
        $query = $grid->model()->with('hierarchy','cards','orderRecharge','orderWithdrawal');
        //->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0);
        if (!empty($data) && isset($data['__search__'])) {
            $query->orderBy('created_at', 'desc')->paginate();
        } elseif (Admin::user()->isRole('servers')) {
            // 3 是为了不让查到 0和1
            $query->where('id','<',0)->orderBy('id', 'desc')->paginate();
        } else {
            $query->select('users.*')->orderBy('id', 'desc')->paginate();
        }

        $grid->disableCreateButton();
        //if (Admin::user()->isRole('servers')) {
        //    $grid->disableFilter();
        //}
        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();

        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        $grid->actions(function ($actions) {
            $actions->add(new JumpTo());
            if (Admin::user()->isRole('servers')) {
                $actions->disableEdit();
            } else {
                $actions->add(new Recharge());
                $actions->add(new Deduction());
                $actions->add(new ResetPassword());
                $actions->add(new BankAction());
                //$actions->add(new AdminAddFundAmount);
                //$actions->add(new AdminSubFundAmount);
            }
//            $actions->add(new ResetPayPassword());
            $actions->disableView();
            $actions->disableDelete();
        });

        $grid->quickSearch('phone')->placeholder('输入手机号');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->column(1 / 2, function ($filter) {
                $filter->like('id', 'ID');
//                $filter->like('name', '会员');
                $filter->like('phone', '手机');
                $filter->like('relation', '查询下级会员');
                $filter->equal('ip_register', '注册IP');
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('hierarchy_id', '层级')->select($this->hierarchy());
                $filter->equal('vip_id')->select('/api/v1/type_vips');
                $filter->between('created_at', '注册时间')->datetime();
            });
        });

        $grid->column('id', __('Id'));
        $grid->column('vip.name', __('Vip'));
        $grid->column('hierarchy.title', '层级');
//        $grid->column('name', __('用户名'));//->modal('详情', ShowUser::class)


        $countries = Country::getCountries();
        $countriesdata = [];
        foreach ($countries as $item){
            $countriesdata[$item->country_i] = $item->country_name;
        }
        $grid->column('country', '国家')->display(function ($var)use($countriesdata){
            if(!empty($var) && isset($countriesdata[$var])){
                return $countriesdata[$var] . '-' .$this->currery;
            }
        });

        $grid->column('phone', '手机')->display(function ($var){
            if(!empty($this->card)){
                $var .= '<br>'.$this->card->number;
            }
            return $var;
        });
        $grid->column('relation')->hide();
        $grid->column('relation_up', '上级ID')->display(function () {
            $user_ids = explode(',', trim($this->relation, ','));
            $relation = User::find(end($user_ids), ['name', 'phone']);
            $relation_user = end($user_ids);
            if ($relation) {
                $relation_user = end($user_ids) . '<br>' . $relation['name'];
            }
            return $relation_user;
        });
        $grid->column('relation_up_up', '上上级ID')->display(function () {
            $user_ids = explode(',', trim($this->relation, ','));
            if (count($user_ids) > 1) {
                $user_ids = array_reverse($user_ids);
                $relation = User::find($user_ids[1], ['name', 'phone']);
                $relation_user = end($user_ids);
                if ($relation) {
                    $relation_user = end($user_ids) . '<br>' . $relation['name'];
                }
                return $relation_user;
            }
            return null;
        });
        $grid->column('balance', '余额')->display(function ($var) {
            return $var / 100;
        });

        $grid->column('orderRecharge', '充值')->display(function ($var) {
            $str = $this->orderRecharge->sum('amount')/100 . "({$this->orderRecharge->count()}次)";
            return $str;
        });

        $grid->column('orderWithdrawal', '提现')->display(function ($var) {
            $str = $this->orderWithdrawal->sum('actual_amount')/100 . "({$this->orderWithdrawal->count()}次)";
            return $str;
        });
        $grid->column('discount_switch', '折扣开关')->bool();
        //$grid->column('fund_amount', '众筹冻结金额')->display(fn ($fundAmount) => bcdiv((string)$fundAmount, 100, 2));
        $grid->column('ip_register', 'IP/设备号')->display(function ($var){
            $str =  '注册IP: '. $var . '(未知)';
            if(!empty($var)){
                try{
                    $reader = Reader::connect();
                    $result = $reader->city($var);
                    if(isset($result->country->names['zh-CN'])){
                        $str =  '注册IP: '. $var . '('. $result->country->names['zh-CN'] .')';
                    }
                }catch(\Exception $e){
                    $str =  '注册IP: '. $var;
                }

            }
            $str .= "<br>最后登录IP: " . $this->ip_last;
            // 设备号可点击，弹窗显示
            $deviceNumber = $this->device_number;
            if(!empty($deviceNumber)){
                $baseUrl = Configuration::getItem('coin_check_device');
                if(!empty($baseUrl)){
                    // 判断URL是否已包含查询参数
                    $deviceUrl = $baseUrl . '/client/device/info?device_number=' . urlencode($deviceNumber);
                    $str .= "<br><a href='javascript:void(0)' onclick=\"window.open('{$deviceUrl}', '设备详情', 'width=800,height=600,scrollbars=yes,resizable=yes')\" style='color: #3c8dbc; text-decoration: underline; cursor: pointer;'>{$deviceNumber}</a>";
                } else {
                    $str .= "<br>" . $deviceNumber;
                }
            } else {
                $str .= "<br>";
            }
            return $str;
        });
        $grid->column('created_at', '注册时间');
        $grid->column('forbid', '封号')->display(function ($var) {
            return $var ? '<i class="fa fa-user-times text-warning"></i>' : '';
        });
        $grid->column('method', '渠道');
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(User::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('email', '头像');
        $show->field('name', '用户名');
        //$show->field('real_name', '真实姓名');
        $show->field('password', __('Password'));
        $show->field('remember_token', __('Remember token'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new User());
        $form->tools(function (Form\Tools $tools) {
            // 去掉`查看`按钮
            $tools->disableView();
            // 去掉`删除`按钮
            $tools->disableDelete();
        });
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });
        $form->text('name', '用户名')
            ->creationRules(['required', "unique:users"])
            ->updateRules(['required', "unique:users,name,{{id}}"]);
        $form->text('phone', '电话')
            ->creationRules(['required', "unique:users"])
            ->updateRules(['required', "unique:users,phone,{{id}}"]);
        if (Route::currentRouteName() != 'admin.users.edit') {
            $form->password('password', '密码')->required();
        }
        $form->select('vip_id', 'VIP')->options('/api/v1/type_vips')->default(1);
        $form->select('hierarchy_id', '层级')->options($this->hierarchy())->default(1);
        $form->switch('forbid', '禁用')->states([
            'on' => ['value' => 1, 'text' => '禁用', 'color' => 'danger'],
            'off' => ['value' => 0, 'text' => '启用', 'color' => 'success'],
        ]);
        $form->hidden('activation')->value(0);
        $form->switch('buy_rebate', '购买返利')->states([
            'on' => ['value' => 1, 'text' => '开启', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '关闭', 'color' => 'danger'],
        ]);
        $form->switch('discount_switch', '折扣开关')->states([
            'on' => ['value' => 1, 'text' => '开启', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '关闭', 'color' => 'danger'],
        ]);

        if (Admin::user()->isRole('administrator')) {
            $form->hidden('admin_user_id')->value(0);
        }
        if (Admin::user()->isRole('agents')) {
            $form->hidden('admin_user_id')->value(Admin::user()->id);
        }

        /*
    ->tab('银行卡信息', function ($form) {
    $form->select('card.bank_id', '银行')->options($this->bankList());
    $form->text('card.number', '卡号');
    $form->text('card.ifsc', 'ifsc');
    $form->text('card.username', '真实姓名');
    $form->text('card.phone', '手机号');
    $form->text('card.email', '邮箱');
});
*/

        if ($form->isCreating()) {
            $form->saving(function (Form $form) {
                $form->password = Hash::make($form->password);
                $form->avatar = 1;
                $form->name = $form->phone;
            });
            //保存后回调
            $form->saved(function (Form $form) {
//                if (Admin::user()->isRole('agents')) {
//                    Agent::create([
//                        'admin_user_id' => Admin::user()->id,
//                        'user_id' => $form->model()->id
//                    ]);
//                }
            });
        }else{
            $form->saving(function (Form $form) {
                $form->name = $form->phone;
            });
        }





        return $form;
    }

    public function hierarchy()
    {
        $hierarchies = Hierarchy::all()->toArray();
        $data = [];
        foreach ($hierarchies as $k => $v) {
            $data[$v['id']] = $v['title'];
        }
        return $data;
    }

    public function userDetail($id,Request $request)
    {
        $tab = new Tab();
        $count = count($request->all());
        $active = $count ? array_keys($request->all())[$count - 1] : 'accounts';
        $tab->add('账变记录', $this->accounts($id), $active == 'accounts');
        $tab->add('一级', $this->level($id), $active == 'tab1');
        $tab->add('二级', $this->level($id, 2), $active == 'tab2');
        $tab->add('三级', $this->level($id, 3), $active == 'tab3');
        echo $tab->render();
    }

    public function accounts($id, $tab = 'accounts')
    {
        $grid = new Grid(new Account());
        //$grid->model()->where('to', $id)->orWhere('from', $id)->paginate(15, ['*'], $tab);
        $grid->model()->where(function ($query) use ($id) {
            $query->where(function ($query) use ($id) {
                $query->where('to', '=', $id);
            })->orWhere(function ($query) use ($id) {
                $query->where('from', '=', $id)->where('amount', '<', 0);
            });
        })
            ->whereNotIn('type',[17])->paginate(15, ['*'], $tab)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        $grid->column('type', '类型')->display(function ($var) {
            return transtable('account_type.'.$var  , 'zh-CN');
        });
        $grid->column('amount', '金额')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('created_at', '时间');
        $grid->disableRowSelector();
        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableFilter();
        return $grid->render();
    }

    public function level($id, $level = 1)
    {
        $grid = new Grid(new User());
        //->withCount('pet_orders')
        $grid->model()->with('hierarchy','cards','orderRecharge','orderWithdrawal')->where('relation', 'regexp',sprintf(',%d,%s$', $id, str_repeat('[0-9]+,', $level - 1)))->paginate(15, ['*'], 'tab'.$level);



        $grid->column('id', __('Id'));
        $grid->column('vip.name', __('Vip'));
        $grid->column('hierarchy.title', '层级');
        $countries = Country::getCountries();
        $countriesdata = [];
        foreach ($countries as $item){
            $countriesdata[$item->country_i] = $item->country_name;
        }
        $grid->column('country', '国家')->display(function ($var)use($countriesdata){
            if(!empty($var) && isset($countriesdata[$var])){
                return $countriesdata[$var];
            }
        });

        $grid->column('phone', '手机')->display(function ($var){
            if(!empty($this->cards)){
                foreach ($this->cards as $card){
                    $var .= '<br>'.$card->number;
                }
            }
            return $var;
        });
        $grid->column('relation')->hide();
        $grid->column('relation_up', '上级ID')->display(function () {
            $user_ids = explode(',', trim($this->relation, ','));
            $relation = User::find(end($user_ids), ['name', 'phone']);
            $relation_user = end($user_ids);
            if ($relation) {
                $relation_user = end($user_ids) . '<br>' . $relation['name'];
            }
            return $relation_user;
        });
        $grid->column('relation_up_up', '上上级ID')->display(function () {
            $user_ids = explode(',', trim($this->relation, ','));
            if (count($user_ids) > 1) {
                $user_ids = array_reverse($user_ids);
                $relation = User::find($user_ids[1], ['name', 'phone']);
                $relation_user = end($user_ids);
                if ($relation) {
                    $relation_user = end($user_ids) . '<br>' . $relation['name'];
                }
                return $relation_user;
            }
            return null;
        });
        $grid->column('balance', '余额')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('orderRecharge', '充值')->display(function ($var) {
            $str = $this->orderRecharge->sum('amount')/100 . "({$this->orderRecharge->count()}次)";
            return $str;
        });
        $grid->column('orderWithdrawal', '提现')->display(function ($var) {
            $str = $this->orderWithdrawal->sum('actual_amount')/100 . "({$this->orderWithdrawal->count()}次)";
            return $str;
        });
        $grid->column('discount_switch', '折扣开关')->bool();
        //$grid->column('fund_amount', '众筹冻结金额')->display(fn ($fundAmount) => bcdiv((string)$fundAmount, 100, 2));
        $grid->column('ip_register', 'IP/设备号')->display(function ($var){
            $str =  '注册IP: '. $var . '(未知)';
            if(!empty($var)){
                $reader = Reader::connect();
                $result = $reader->city($var);
                if(isset($result->country->names['zh-CN'])){
                    $str =  '注册IP: '. $var . '('. $result->country->names['zh-CN'] .')';
                }
            }
            $str .= "<br>最后登录IP: " . $this->ip_last;
            // 设备号可点击，弹窗显示
            $deviceNumber = $this->device_number;
            if(!empty($deviceNumber)){
                $baseUrl = Configuration::getItem('coin_check_device');
                if(!empty($baseUrl)){
                    // 判断URL是否已包含查询参数
                    $deviceUrl = $baseUrl . '/client/device/info?device_number=' . urlencode($deviceNumber);
                    $str .= "<br><a href='javascript:void(0)' onclick=\"window.open('{$deviceUrl}', '设备详情', 'width=800,height=600,scrollbars=yes,resizable=yes')\" style='color: #3c8dbc; text-decoration: underline; cursor: pointer;'>{$deviceNumber}</a>";
                } else {
                    $str .= "<br>" . $deviceNumber;
                }
            }
            return $str;
        });
        $grid->column('created_at', '注册时间');
        $grid->column('forbid', '封号')->display(function ($var) {
            return $var ? '<i class="fa fa-user-times text-warning"></i>' : '';
        });





        /*        $grid->column('id', 'Id');
                $grid->column('name', '手机');
                //$grid->column('pet_orders_count', '交易数量');
                $grid->column('created_at', '注册时间');*/


        $grid->tools(function ($tools) {
            /*
                $tools->append(<<<EOT
    <button id="back" class="btn btn-sm btn-default" onclick="javascript:history.back()">返回</button>
    EOT
                );
            */
        });
        $grid->disableActions();
        $grid->disableColumnSelector();
        $grid->disableRowSelector();
        $grid->disableCreateButton();
        $grid->disableFilter();
        $grid->disableExport();
        return $grid->render();
    }

    public function bankList()
    {
        $bank = Bank::all()->toArray();
        $bank_list = [];
        foreach ($bank as $k => $v) {
            $bank_list[$v['id']] = $v['name'];
        }
        return $bank_list;
    }
}
