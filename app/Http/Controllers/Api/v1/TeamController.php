<?php

namespace App\Http\Controllers\Api\v1;

use App\Handlers\KeysHandler;
use App\Http\Requests\Api\v1\TeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * 团队
 * @package App\Http\Controllers\Api\v1
 */
class TeamController extends Controller
{
    public function index(TeamRequest $request)
    {
        $user = $request->user();
        $date_type = $request->get('date_type', 'all');  // all , today, yestoday
        $date_start = $request->get('date_start', null);
        $date_end = $request->get('date_end', null);
        $start = null;
        $end = null;
        if(!empty($date_start) || !empty($date_end)){
            if(!empty($date_start)){
                $start = Carbon::parse($date_start)->startOfDay();
            }
            if(!empty($date_end)){
                $end = Carbon::parse($date_end)->endOfDay();
            }

        }else{
            if($date_type == 'today'){
                $start = Carbon::now()->startOfDay();
                $end = Carbon::now()->endOfDay();
            }elseif ($date_type == 'yestoday'){
                $start = Carbon::yesterday()->startOfDay();
                $end = Carbon::yesterday()->endOfDay();
            }
        }

        $subordinate = $user->countSubordinate(0,$date_type, $start, $end);
        $recharge_num = $user->countSubordinateOrder(0,$date_type, $start, $end);
        $recharge_first_num = $user->countSubordinateOrderFirst(0,$date_type, $start, $end);
        $pet_user_num = $user->countSubordinatePetOrder(0,$date_type, $start, $end);

        $data = [
            'commission' => $user->team_commission,  //佣金
            'subordinate' => $subordinate,
            'recharge_num' => $recharge_num,
            'recharge_first_num' => $recharge_first_num,
            'pet_user_num' => $pet_user_num,
            'list' => [],
            'recharge' => [
                'level_1' => $user->countSubordinateOrder(1,$date_type, $start, $end),
                'level_2' => $user->countSubordinateOrder(2,$date_type, $start, $end),
                'level_3' => $user->countSubordinateOrder(3,$date_type, $start, $end)
            ],
            'count' => [
                'level_1' => $user->countSubordinate(1,$date_type, $start, $end),
                'level_2' => $user->countSubordinate(2,$date_type, $start, $end),
                'level_3' => $user->countSubordinate(3,$date_type, $start, $end),
            ]
        ];

        return $this->success($data);
    }

    public function users(TeamRequest $request)
    {
        $user = $request->user();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $level = $request->get('level', 1);
        $page = $this->paging($page, $limit);
        $date = $request->date ?: '';
        $showzero = $request->get('showzero', 0);
        $phone = $request->get('phone', '');
        $column = $request->get('column', '');
        $sort = $request->get('sort', '');
        return $this->success($this->getUsers($user, $level, $page, $limit, $date, $showzero, $phone, $column, $sort));
    }

    private function getUsers($user, $level, $page, $limit, $date, $showzero, $phone, $column, $sort)
    {

        return Cache::remember(sprintf(KeysHandler::USER_TEAM, $user->id, $level, $page, $limit, $date, $showzero, $phone, $column, $sort), KeysHandler::USER_PROFIT_EXPIRE,
            function () use ($user, $level, $page, $limit, $date, $showzero, $phone, $column, $sort) {
                $users = $user->subordinate_amount($level, $page, $limit, $date, $showzero, $phone, $column, $sort);
                //$users = $user->subordinate($level, $page, $limit ,$date);
                if ($users) {
                    //$account = new Account();
                    foreach ($users as $k => $u) {
                        //$u->amount = $account->calculateRevenueFromUser($user->id, $u->id);
                        //if($showzero && $u->amount<=0){
                        //unset($users[$k]);
                        //}
                        if ($u->amount == null) {
                            $u->amount = 0;
                        }
                        /*
                        $users[$k]->link = '';
                        if ($u->hidephone == 0) {
                            $u->link = sprintf('%s=63%d', User::WHATAPP_URL, $u->name);
                        }
                        if ($u->hidephone == 1) {
                            $users[$k]->name = sprintf('%s***%s', substr($u->name, 0, 3), substr($u->name, -3));
                        }
                        */
                        unset($users[$k]->hidephone);
                    }
                }
                return [
                    'count' => [
                        'level_1' => $user->getSubordinateLevelCount($user, $date ? Carbon::parse($date) : null, $showzero, 1, $phone),
                        'level_2' => $user->getSubordinateLevelCount($user, $date ? Carbon::parse($date) : null, $showzero, 2, $phone),
                        'level_3' => $user->getSubordinateLevelCount($user, $date ? Carbon::parse($date) : null, $showzero, 3, $phone),
                    ],
                    'recharge' => [
                        'level_1' => $user->countSubordinateOrder(1),
                        'level_2' => $user->countSubordinateOrder(2),
                        'level_3' => $user->countSubordinateOrder(3)
                    ],
                    'list' => TeamResource::collection($users),
                ];
            });
    }

    // 下级收益
    public function subordinate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'level' => 'required|numeric|between:1,3',
            'page' => 'required|numeric|min:1',
            'limit' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return $this->fail(400, $validator->errors()->first());
        }

        $limit = $request->get('limit', 10);
        $page = $this->paging($request->get('page', 1), $limit);

        $id = $request->user()->id;
        $data = (new Account())->getSubordinateProfit($id, $request->level, $page, $limit);
        return $this->success(TeamResource::collection($data));
    }


    public function getcommission(Request $request){
        DB::transaction(function () use ($request) {
            $user = $request->user();
            $amount = $user->team_commission;
            if($amount>0){
                $user->decrement('team_commission', $amount);
                $user->increment('balance', $amount);

                Account::create([
                    'type' => Account::REVENUE_COMMISSION,
                    'to' => $user->id,
                    'amount' => $amount
                ]);

                //app(Account::class)->store($user->id, $amount, Account::COMMISSION);
                Cache::forget('team_statistics_user_'.$user->id);
            }
        });

        return $this->success();
    }


}
