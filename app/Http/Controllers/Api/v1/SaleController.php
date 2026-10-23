<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Account;
use App\Models\Card;
use App\Models\Configuration;
use App\Models\TelegramBots;
use App\Models\TelegramCrond;
use App\Models\TelegramGroup;
use App\Models\TelegramTemplate;
use App\Handlers\KeysHandler;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SaleController extends Controller
{

    //业务员后台
    public function __construct()
    {

    }


    /*
     * 首页统计
     * 群组统计: 总群组,昨日新增,今日新增
     * 存款人数: 总存款,昨日存款人数,今日存款人数,昨日首存人数,今日首存人数
     * 存款金额: 总存款金额,昨日存款金额,今日存款金额,总提款金额,平台毛利
     * */
    public function index(Request $request){
        $user = $request->user();
        $today = Carbon::now();
        $yestoday = Carbon::yesterday();

        //总群组
        $total_group= TelegramGroup::query()
            ->when($user->hierarchy_id == 7, function ($query) use ($user) {
                $query->where('user_id','=',$user->id);
            })
            ->get();
        $agent_ids = [];
        foreach ($total_group as $val){
            if(!in_array($val->agent_id, $agent_ids)){
                $agent_ids[] = $val->agent_id;
            }
        }
        $total_group_count = $total_group->count();
        //昨日新增
        $today_add_group_count = TelegramGroup::query()
            ->when($user->hierarchy_id == 7, function ($query) use ($user) {
                $query->where('user_id','=',$user->id);
            })
            ->whereDate('created_at','=', $today)
            ->count();
        //今日新增
        $yestoday_add_group_count = TelegramGroup::query()
            ->when($user->hierarchy_id == 7, function ($query) use ($user) {
                $query->where('user_id','=',$user->id);
            })
            ->whereDate('created_at','=', $yestoday)
            ->count();

        $subRecharge = TelegramGroup::getSubRecharge($agent_ids);
        $todaySubRecharge = TelegramGroup::getSubRecharge($agent_ids, 'today');
        $yestodaySubRecharge = TelegramGroup::getSubRecharge($agent_ids, 'yestoday');

        $todaySubFitstRechargeCount = TelegramGroup::getSubFitstRechargeCount($agent_ids, 'today');
        $yestodaySubFitstRechargeCount = TelegramGroup::getSubFitstRechargeCount($agent_ids, 'yestoday');

        $subWithdrawal = TelegramGroup::getSubWithdrawal($agent_ids);

        $data['total_group_count'] = $total_group_count;
        $data['today_add_group_count'] = $today_add_group_count;
        $data['yestoday_add_group_count'] = $yestoday_add_group_count;

        $data['total_recharge_count'] = $subRecharge['count'];  //总存款人数
        $data['yestoday_recharge_count'] = $yestodaySubRecharge['count'];  //昨日存款人数
        $data['today_recharge_count'] = $todaySubRecharge['count'];  //今日存款人数
        $data['yestoday_first_recharge_count'] = $todaySubFitstRechargeCount;  //昨日首次存款人数
        $data['today_first_recharge_count'] = $yestodaySubFitstRechargeCount;  //今日首次存款人数

        $data['total_recharge'] = $subRecharge['amount'];  //总存款金额
        $data['yestoday_recharge'] = $yestodaySubRecharge['amount'];  //昨日存款金额
        $data['today_recharge'] = $todaySubRecharge['amount'];  //今日存款金额

        $data['total_withdrawal'] = $subWithdrawal['amount'];  //总提款金额
        $data['profit'] = floatval(round($data['total_recharge'] - $data['total_withdrawal'], 2));  //平台毛利
        return $this->success($data);
    }


    /*
     * 群组列表
     * ID  业务员(管理员),群名称(点击到群链接),代理账号,存款金额,提款金额,昨日首存,今日首存,创建日期,广告机器人,广告模版
     * */
    public function groupLists(Request $request){
        $user = $request->user();
        $pageSize = $request->get('page_size', 10);
        $name = $request->get('name');
        $bot_name = $request->get('bot_name');
        $template_name = $request->get('template_name');
        $agent_phone = $request->get('agent_phone');  //代理账号
        $chart_id = $request->get('chart_id');  //群聊ID

        $list_query = TelegramGroup::query()
            ->with([
                'user' => function($q){
                    $q->select('id','name');
                },
                'agent_user' => function($q){
                    $q->select('id','name');
                },
                'bot' => function($q){
                    $q->select('id','bot_name');
                },
                'template' => function($q){
                    $q->select('id','name');
                }
            ])
            ->when($name, function ($query) use ($name) {
                $query->where('name','like', "%$name%");
            })
            ->when($user->hierarchy_id == 7, function ($query) use ($user) {
                $query->where('user_id','=',$user->id);
            })
            ->when($chart_id, function ($query) use ($chart_id) {
                $query->where('chart_id','=', $chart_id);
            })
            ->when($bot_name, function ($query) use ($bot_name) {
                $query->whereHas('bot',function($q) use ($bot_name) {
                    $q->where('bot_name','like', "%$bot_name%");
                });
            })
            ->when($template_name, function ($query) use ($template_name) {
                $query->whereHas('template',function($q) use ($template_name) {
                    $q->where('name','like', "%$template_name%");
                });
            })
            ->when($agent_phone, function ($query) use ($agent_phone) {
                $query->whereHas('agent_user',function($q) use ($agent_phone) {
                    $q->where('name','like', "%$agent_phone%");
                });
            });

        $list = $list_query->orderByDesc('id')->paginate($pageSize);
        $datas = $list->items();
        foreach ($datas as &$val){

            $val->subRecharge = TelegramGroup::getSubRecharge([$val->agent_id])['amount'];
            $val->subWithdrawal = TelegramGroup::getSubWithdrawal([$val->agent_id])['amount'];
            $val->todaySubFitstRechargeCount = TelegramGroup::getSubFitstRechargeCount([$val->agent_id], 'today');
            $val->yestodaySubFitstRechargeCount = TelegramGroup::getSubFitstRechargeCount([$val->agent_id], 'yestoday');
        }

        $data = [
            'list' => $datas,
            'paginate' => $this->paginate($list)
        ];
        return $this->success($data);
    }

    /*
     * 查看群组
     * 群名称(点击到群链接),代理账号,创建日期,存款人数/金额,提款人数/金额,平台毛利,昨日新增首存
     * */
    public function groupShow(Request $request){
        //$user = $request->user();
        $validatedData = $request->validate([
            'group_id'    => ['required', 'numeric']
        ]);
        $show = TelegramGroup::query()
            ->where('id','=', $validatedData['group_id'])
            ->with([
                'user' => function($q){
                    $q->select('id','name');
                },
                'agent_user' => function($q){
                    $q->select('id','name');
                },
                'bot' => function($q){
                    $q->select('id','bot_name');
                },
                'template' => function($q){
                    $q->select('id','name');
                }
            ])
            ->first();

        $getSubRecharge = TelegramGroup::getSubRecharge([$show->agent_id]);
        $subWithdrawal = TelegramGroup::getSubWithdrawal([$show->agent_id]);
        $show->subRecharge = $getSubRecharge['amount'];
        $show->subRechargeCount = $getSubRecharge['count'];
        $show->subWithdrawal = $subWithdrawal['amount'];
        $show->subWithdrawalCount = $subWithdrawal['count'];
        $show->todaySubFitstRechargeCount = TelegramGroup::getSubFitstRechargeCount([$show->agent_id], 'today');
        $show->yestodaySubFitstRechargeCount = TelegramGroup::getSubFitstRechargeCount([$show->agent_id], 'yestoday');
        $show->profit = floatval(round($show->subRecharge - $show->subWithdrawal, 2));

        return $this->success($show);
    }

    /**
     * 添加群组
     * 群名称,群链接,群ID,代理账号,广告机器人,广告模版
     * */
    public function groupAdd(Request $request){
        $user = $request->user();
        $validatedData = $request->validate([
            'name' => ['required', 'max:255'],
            'group_link' => ['required'],
            'chart_id' => ['required'],
            'bot_id' => ['required','numeric'],
            'template_id' => ['required','numeric'],
            'agent_phone' => ['required']
        ]);

        //查询代理账号是否存在
        $agent_user = User::query()->where('name','=',$validatedData['agent_phone'])->first();
        if(!$agent_user){
            $this->errorResponse(Response::HTTP_OK,transtable('100001'),100001);
        }

        TelegramGroup::query()->create([
            'user_id' => $user->id,
            'name' => $validatedData['name'],
            'group_link' => $validatedData['group_link'],
            'chart_id' => $validatedData['chart_id'],
            'bot_id' => $validatedData['bot_id'],
            'template_id' => $validatedData['template_id'],
            'agent_id' => $agent_user->id
        ]);
        return $this->success();
    }

    /**
     * 编辑群组
     * 群名称,群链接,群ID,代理账号,存款金额,提款金额,昨日新增首存,今日新增首存,创建日期
     * */
    public function groupEdit(Request $request){
        //$user = $request->user();
        $validatedData = $request->validate([
            'group_id'    => ['required', 'numeric'],
            'name' => ['required', 'max:255'],
            'group_link' => ['required'],
            'chart_id' => ['required'],
            'bot_id' => ['required','numeric'],
            'template_id' => ['required','numeric'],
            'agent_phone' => ['required']
        ]);
        //查询代理账号是否存在
        $agent_user = User::query()->where('name','=',$validatedData['agent_phone'])->first();
        if(!$agent_user){
            $this->errorResponse(Response::HTTP_OK,transtable('100001'),100001);
        }
        TelegramGroup::query()->where('id','=', $validatedData['group_id'])->update([
            'name' => $validatedData['name'],
            'group_link' => $validatedData['group_link'],
            'chart_id' => $validatedData['chart_id'],
            'bot_id' => $validatedData['bot_id'],
            'template_id' => $validatedData['template_id'],
            'agent_id' => $agent_user->id
        ]);
        return $this->success();
    }

    /**
     * 删除群组
     * */
    public function groupDel(Request $request){
        $user = $request->user();
        $validatedData = $request->validate([
            'group_id'    => ['required', 'numeric']
        ]);
        if($user->hierarchy_id != 8 && TelegramGroup::query()->where('id','=', $validatedData['group_id'])->where('user_id','=',$user->id)->doesntExist()){
            $this->errorResponse(Response::HTTP_OK,transtable('401'),401);
        }
        TelegramGroup::query()->where('id','=', $validatedData['group_id'])->delete();
        return $this->success();
    }

    /**
     * 实时发送广告消息
     * */
    public function groupSendAd(Request $request){
        $validatedData = $request->validate([
            'group_id'    => ['required', 'numeric']
        ]);
        TelegramCrond::sendMessageGroup($validatedData['group_id'] , 'ad');
        return $this->success();
    }


    /**
     * 机器人列表
     * */
    public function botLists(Request $request){
        $pageSize = $request->get('page_size', 10);
        $bot_name = $request->get('bot_name');
        $bot_list = TelegramBots::query()
            ->withCount('group')
            ->when($bot_name, function ($query) use ($bot_name) {
                $query->where('bot_name','like', "%$bot_name%");
            })
            ->orderByDesc('id')->paginate($pageSize);

        $data = [
            'list' => $bot_list->items(),
            'paginate' => $this->paginate($bot_list)
        ];
        return $this->success($data);
    }

    /**
     * 添加机器人
     * */
    public function botAdd(Request $request){
        $user = $request->user();
        if($user->hierarchy_id != 8){
            $this->errorResponse(Response::HTTP_OK,transtable('401'),401);
        }
        $validatedData = $request->validate([
            'bot_name' => ['required', 'max:255'],
            'bot_token' => ['required']
        ]);

        TelegramBots::query()->create([
            'bot_name' => $validatedData['bot_name'],
            'bot_token' => $validatedData['bot_token'],
        ]);
        return $this->success();
    }

    /**
     * 编辑机器人
     * */
    public function botEdit(Request $request){
        $user = $request->user();
        if($user->hierarchy_id != 8){
            $this->errorResponse(Response::HTTP_OK,transtable('401'),401);
        }
        $validatedData = $request->validate([
            'bot_id'    => ['required', 'numeric'],
            'bot_name'  => ['required', 'max:255'],
            'bot_token' => ['required'],
            'status'    => ['required']
        ]);
        TelegramBots::query()->where('id','=', $validatedData['bot_id'])->update([
            'bot_name' => $validatedData['bot_name'],
            'bot_token' => $validatedData['bot_token'],
            'status' => $validatedData['status']
        ]);
        return $this->success();
    }

    /**
     * 删除机器人
     * */
    public function botDel(Request $request){
        $user = $request->user();
        if($user->hierarchy_id != 8){
            $this->errorResponse(Response::HTTP_OK,transtable('401'),401);
        }
        $validatedData = $request->validate([
            'bot_id'    => ['required', 'numeric']
        ]);
        TelegramBots::query()->where('id','=', $validatedData['bot_id'])->delete();
        return $this->success();
    }


    /**
     * 模版列表
     * */
    public function templateLists(Request $request){
        $user = $request->user();
        $pageSize = $request->get('page_size', 10);

        $name = $request->get('name');

        $list_query = TelegramTemplate::query()
            ->with('user:id,name')
            ->when($name, function ($query) use ($name) {
                $query->where('name','like', "%$name%");
            });

        if($user->hierarchy_id == 7){
            $list_query->where('user_id','=',$user->id);
        }
        $list = $list_query->orderByDesc('id')->paginate($pageSize);
        $data = [
            'list' => $list->items(),
            'paginate' => $this->paginate($list)
        ];
        return $this->success($data);
    }

    /**
     * 添加模版
     * */
    public function templateAdd(Request $request){
        $user = $request->user();
        $validatedData = $request->validate([
            'name' => ['required', 'max:255'],
            'content' => ['required']
        ]);
        TelegramTemplate::query()->create([
            'user_id' => $user->id,
            'name' => $validatedData['name'],
            'content' => $validatedData['content']
        ]);
        return $this->success();
    }

    /**
     * 编辑模版
     * */
    public function templateEdit(Request $request){
        $user = $request->user();
        $validatedData = $request->validate([
            'template_id'    => ['required', 'numeric'],
            'name'  => ['required', 'max:255'],
            'content' => ['required']
        ]);
        if($user->hierarchy_id != 8 && TelegramTemplate::query()->where('id','=', $validatedData['template_id'])->where('user_id','=',$user->id)->doesntExist()){
            $this->errorResponse(Response::HTTP_OK,transtable('401'),401);
        }

        TelegramTemplate::query()->where('id','=', $validatedData['template_id'])->update([
            'name' => $validatedData['name'],
            'content' => $validatedData['content']
        ]);
        return $this->success();
    }

    /**
     * 删除模版
     * */
    public function templateDel(Request $request){
        $user = $request->user();
        $validatedData = $request->validate([
            'template_id'    => ['required', 'numeric']
        ]);
        if($user->hierarchy_id != 8 && TelegramTemplate::query()->where('id','=', $validatedData['template_id'])->where('user_id','=',$user->id)->doesntExist()){
            $this->errorResponse(Response::HTTP_OK,transtable('401'),401);
        }
        TelegramTemplate::query()->where('id','=', $validatedData['template_id'])->delete();
        return $this->success();
    }


    /**
     * 群组列表枚举
     * */
    public function groupListsSelect(Request $request){
        $name = $request->get('name');
        $list = TelegramGroup::query()
            ->select('id','name')
            ->when($name, function ($query) use ($name) {
                $query->where('name','like', "%$name%");
            })->orderByDesc('id')->get();;

        return $this->success($list);
    }

    /**
     * 计划列表
     * */
    public function crondLists(Request $request){
        $user = $request->user();
        $pageSize = $request->get('page_size', 10);
        $group_name= $request->get('name');
        if($user->hierarchy_id != 8){
            $this->errorResponse(Response::HTTP_OK,transtable('401'),401);
        }
        //$group_id = $request->get('group_id');

        $groups =  TelegramGroup::query()->select('id','name')->get()->toArray();
        $group_arr = [];
        foreach ($groups as $val){
            $group_arr[$val['id']] = $val['name'];
        }

        $bot_list = TelegramCrond::query()
            //->with('group:id,name')
/*
            ->when($group_name, function ($query) use ($group_name) {
                $query->whereHas('group',function($q) use ($group_name) {
                    $q->where('name','like', "%$group_name%");
                });
            })
*/
            ->orderByDesc('id')->paginate($pageSize);

            foreach ($bot_list as &$val){
                $group_ids_arr = explode(',',$val->group_id);
                $group_names_arr = [];
                foreach ($group_ids_arr as $id){
                    if(isset($group_arr[$id])){
                        $group_names_arr[] = $group_arr[$id];
                    }
                }
                $val->group_names = implode(',',$group_names_arr);

            }


        $data = [
            'list' => $bot_list->items(),
            'paginate' => $this->paginate($bot_list)
        ];
        return $this->success($data);
    }


    /**
     * 添加计划
     * 设置的时间点  把没有发送过的 晒单圈里面的图 都发出去
     * */
    public function crondAdd(Request $request){
        $user = $request->user();
        if($user->hierarchy_id != 8){
            $this->errorResponse(Response::HTTP_OK,transtable('401'),401);
        }
        $validatedData = $request->validate([
            'group_id'  => ['required'],
            'send_time' => ['required'],
            'type'      => ['required']
        ]);

        TelegramCrond::query()->create([
            'group_id' => $validatedData['group_id'],
            'send_time' => $validatedData['send_time'],
            'type'      => $validatedData['type']
        ]);
        return $this->success();
    }

    /**
     * 编辑计划
     * */
    public function crondEdit(Request $request){
        $user = $request->user();
        if($user->hierarchy_id != 8){
            $this->errorResponse(Response::HTTP_OK,transtable('401'),401);
        }
        $validatedData = $request->validate([
            'crond_id'    => ['required', 'numeric'],
            'group_id' => ['required'],
            'send_time' => ['required'],
            'type'      => ['required']
        ]);
        TelegramCrond::query()->where('id','=', $validatedData['crond_id'])->update([
            'group_id' => $validatedData['group_id'],
            'send_time' => $validatedData['send_time'],
            'type'      => $validatedData['type']
        ]);
        return $this->success();
    }

    /**
     * 删除计划
     * */
    public function crondDel(Request $request){
        $user = $request->user();
        if($user->hierarchy_id != 8){
            $this->errorResponse(Response::HTTP_OK,transtable('401'),401);
        }
        $validatedData = $request->validate([
            'crond_id'    => ['required', 'numeric']
        ]);
        TelegramCrond::query()->where('id','=', $validatedData['crond_id'])->delete();
        return $this->success();
    }







}
