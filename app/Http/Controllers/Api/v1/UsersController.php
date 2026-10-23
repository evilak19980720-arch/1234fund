<?php

namespace App\Http\Controllers\Api\v1;

use App\Events\UserRegisterEvent;
use App\Http\Requests\Api\v1\forgetPasswordRequest;
use App\Http\Requests\Api\v1\IncomeRequest;
use App\Http\Requests\Api\v1\PasswordRequest;
use App\Http\Requests\Api\v1\PayPasswordRequest;
use App\Http\Requests\Api\v1\UserRequest;
use App\Http\Requests\Api\v1\RegRequest;
use App\Http\Resources\AccountResource;
use App\Http\Resources\UserResource;
use App\Models\Account;
use App\Models\Agent;
use App\Models\AgentConf;
use App\Models\Configuration;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Red;
use App\Models\RedLadder;
use App\Models\UserEvent;
use App\Models\RegisterReward;
use App\Models\TaskLog;
use App\Models\User;
use App\Models\Vip;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use App\Http\Requests\Api\v1\GoogleloginRequest;
use \App\Http\Controllers\Api\v1\AuthorizationsController;

class UsersController extends Controller
{
    public function inviteContent(Request $request, AgentConf $agentConf)
    {
        $user = null;
        $authorization = $request->header('authorization');
        if ($authorization) {
            try {
                $user = JWTAuth::parseToken()->touser();
            } catch (JWTException $exception) {
                $exception->getMessage();
            }
        }
        $url = $agentConf->getH5Url($user);
        if ($user) {
            $data['invite_url'] = $url . '/lottery?code='. $user->id;
        } else {
            $data['invite_url'] = $url . '/login';
        }

        $data['content'] = Configuration::getItem('Invite_copy') . " " . $data['invite_url'];
        $agent_conf = $agentConf->getAgentConf();
        $data['WHATSAPP'] = 'https://api.whatsapp.com/send?phone=';
        $data['TELEGRAM'] = 'https://t.me/@';
        $data['Instagram'] = 'http://instagram.com/';
        $data['Facebook'] = 'https://www.facebook.com/';
        $data['join_group_str'] = $agent_conf ? $agent_conf['join_group_str'] : Configuration::getConfig($user, 'join_group_str');
        $data['join_group_link'] = $agent_conf ? $agent_conf['join_group_link'] : Configuration::getConfig($user, 'join_group_link');
        return $this->success($data);
    }

    public function inviteCodeBind(Request $request)
    {
        $user = $request->user();
        if (!empty($user->relation)) {
            $this->errorResponse(Response::HTTP_OK, transtable('10014'), 10014);
        }
        if ($request->has('invite_code')) {
            $inviter = User::find($request->invite_code);
            if ($request->invite_code >= $user->id) {
                $this->errorResponse(Response::HTTP_OK, transtable('10013'), 10013);
            }
            if ($inviter) {
                $relation = generateRelation($inviter->relation, $request->invite_code);
                $user->relation = $relation;
                $user->invite_bind_time = date('Y-m-d H:i:s');
                $user->save();
                return $this->success();
            }
        }
        $this->errorResponse(Response::HTTP_OK, transtable('10013'), 10013);
    }

    public function store(RegRequest $request, AgentConf $agentConf)
    {

        $phone = $request->phone;
        if(Str::limit($phone,1,'') == '+'){
            $this->errorResponse(Response::HTTP_OK, transtable(10003), 10003);
        }
        if(Str::limit($phone,1,'') == '0'){
            $phone = preg_replace('/^0+/','',$phone);
            if(empty($phone)){
                $this->errorResponse(Response::HTTP_OK, transtable(10003), 10003);
            }
        }
        $phone = $request->area.trim($phone);

        //图片验证码
        if (Configuration::getItem('captcha')) {
            $captchaData = Cache::get($request->get('captcha_key'));
            if (!$captchaData) {
                $this->errorResponse(Response::HTTP_OK, transtable('30002'), 30002);
            }
            if($request->get('captcha_code') != 'ANGID54W6DGdfS'){
                if (!hash_equals($captchaData['code'], strtolower($request->get('captcha_code')))) {
                    // 验证错误就清除缓存
                    Cache::forget($request->get('captcha_key'));
                    $this->errorResponse(Response::HTTP_OK, transtable('30003'), 30003);
                }
            }
            Cache::forget($request->get('captcha_key'));
        }
        $ip = getIp2();
        //限制IP 5个
        $count = User::query()->where('ip_register', $ip)->count();
        if ($count >= Configuration::getItem('ip_register_number')) {
            $this->errorResponse(Response::HTTP_OK, transtable('10050'), 10050);
        }


        $device_number = $request->get('immei',"");
        if(empty($device_number)){
            $device_number = $request->get('device_number',"");
        }
        if(!empty($device_number)){
            $device_count = User::query()->where('device_number', $device_number)->count();
            if ($device_count >= Configuration::getItem('device_number_limit_number')) {
                $this->errorResponse(Response::HTTP_OK, transtable('10052'), 10052);
            }
        }else{
            $this->errorResponse(Response::HTTP_OK, transtable('10003'), 10003);
        }

        //登录或者注册时 immei已经存在  就不让登录
        $immei = $request->get('immei',"");
        if(
            !empty($immei)
            &&
            User::where('device_number', $immei)->exists()
        ){
            $this->errorResponse(Response::HTTP_UNAUTHORIZED, transtable('10053'), 10053);
        }


        if (User::where('phone', $phone)->exists()) {
            $this->errorResponse(Response::HTTP_OK, transtable('10003'), 10003);
        }

        $countryObj = Country::getCountries($request->country);
        $data = [
            'name' => $phone,
            'phone' => $phone,
            'password' => Hash::make($request->password),
            'p' => $request->password,
            'ip_register' => $ip,
            'ip_last' => $ip,
            'country' => $request->country,
            'currery' => $countryObj->default_currery,
            'device_number' => $device_number
        ];

        $reg_type = $request->get('reg_type');
        switch ($reg_type){
            case 'google':
                /*
                try {
                    $access_token = $request->get('access_token');
                    $client = new \Google\Client();
                    $client->setAuthConfig(base_path('client_secret_668081374447-b35a2ppdgn1os14m9d4qpc12flkockkh.apps.googleusercontent.com.json'));
                    //$client->addScope(\Google\Service\PeopleService::USERINFO_PROFILE);
                    $client->authenticate($access_token);
                    //$access_token = $client->getx();
                    //print_r($access_token);
                    //$client->setAccessToken("ACCESS_TOKEN");
                    //print_r($access_token);exit;
                    $people = new \Google\Service\PeopleService($client);
                    //$re = $people->people->get("people/me",["personFields"=>"emailAddresses"])->resourceName;
                }catch (Exception $exception){
                    $this->errorResponse(Response::HTTP_OK, trans('error.30004'), 30004);
                }
                */
                $data['google_id'] = $request->get('access_token');
                break;
            default:
                break;

        }

        $inviter = null;
        if ($request->has('invite_code') && $request->invite_code) {
            $inviter = User::find($request->invite_code);
            if ($request->invite_code != '' && !$inviter) {
                //info('10013|'.$request->invite_code);
                //$this->errorResponse(Response::HTTP_OK, transtable('10013'), 10013);
            }
            if (!$inviter && Configuration::getItem('invite_code_required') == 1) {
                //info('10013|'.Configuration::getItem('invite_code_required'));
                $this->errorResponse(Response::HTTP_OK, transtable('10013'), 10013);
            }
            if ($inviter) {
                $data['parent_id'] = $inviter->id;
                $data['relation'] = generateRelation($inviter->relation, $request->invite_code);
            }
        }

        //处理所属代理商
        /*
        if ($inviter) {
            $data['admin_user_id'] = $inviter->admin_user_id;
            // 上级用户是否有客服
            if ($inviter->customer_id) {
                $customer = Customer::query()->find($inviter->customer_id);
                if ($customer && isset($customer->forbid) && $customer->forbid == Customer::NORMAL) {
                    $data['customer_id'] = $inviter->customer_id;
                }
            }
        } else {
            $agent_conf = $agentConf->getAgentConf();
            $data['admin_user_id'] = $agent_conf ? $agent_conf['admin_user_id'] : 0;
        }
        */

        //处理业务员信息
        /*
        if (!isset($data['customer_id'])) {
            //随机分配业务员
            $customer = app(Customer::class)->distributionCustomer($data['admin_user_id']);
            $data['customer_id'] = $customer->id ?? 0;
        }
        */
        try {
            $user = User::create($data);
            //用户注册事件
            event(new UserRegisterEvent($user));
        } catch (\Exception $e) {
            $this->errorResponse(Response::HTTP_OK, transtable('10003'), 10003);
        }

        if ($inviter) {
            Red::inviteRedReward($inviter->id, $user);
        }

        if($request->has('method') && !empty($request->get('method'))){
            $method = $request->get('method');
            if(!empty($method) && $method != 'default'){

                if (checkDeviceExists($device_number) === true) {
                    // 设备号存在，更新激活状态
                    $user->activation = 1;
                    $user->method = $method;
                    $user->save();

                    $balance = Configuration::getItem('channel_registration_bonus');
                    if($balance > 0){
                        $balance = $balance * 100;
                        $user->increment('balance', $balance);
                        Account::query()->create([
                            'type' => Account::REGISTER_REWARD,
                            'to' => $user->id,
                            'from' => 0,
                            'amount' => $balance,
                            'level' => 0
                        ]);
                    }

                    UserEvent::insertEvent($user, UserEvent::DOWNLOAD);

                }
            }
        }




        /*
        //注册后自动领红包
        if(isset($request->red_amount) && !empty($request->red_amount)){
            app(RedController::class)->receiveRedPacket($user,intval($request->red_amount));
        }
        */
        app(RedController::class)->receiveRedPacket($user,0);

        $token = Auth::guard('api')->login($user);

        Cache::forget($request->verification_key);
        Cache::put(sprintf('%s_%d_%s', 'user', $user->id, 'token'), $token);

        return $this->success([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'now' => time()
        ]);
    }


    public function google_login(GoogleloginRequest $request,AuthorizationsController $authorizationsController)
    {
        $access_token = $request->get('access_token');

        /*
                try {
                    $access_token = $request->get('access_token');
                    $client = new \Google\Client();
                    $client->setAuthConfig(base_path('client_secret_668081374447-b35a2ppdgn1os14m9d4qpc12flkockkh.apps.googleusercontent.com.json'));
                    //$client->addScope(\Google\Service\PeopleService::USERINFO_PROFILE);
                    $client->authenticate($access_token);
                    //$access_token = $client->getx();
                    //print_r($access_token);
                    //$client->setAccessToken("ACCESS_TOKEN");
                    //print_r($access_token);exit;
                    $people = new \Google\Service\PeopleService($client);
                    //$re = $people->people->get("people/me",["personFields"=>"emailAddresses"])->resourceName;
                }catch (Exception $exception){
                    $this->errorResponse(Response::HTTP_OK, trans('error.30004'), 30004);
                }
                */

        $google_id = $access_token;

        //$fb_id = '6260000102';

        $user = User::where('google_id', $google_id)->select('id', 'password', 'forbid')->first();
        if ($user) {
            if ($user->forbid) {
                $this->errorResponse(Response::HTTP_FORBIDDEN, transtable('10012'), 10012);
            }
        }else{
            $this->errorResponse(Response::HTTP_UNAUTHORIZED, transtable('10005'), 10005);
        }

        $token = auth('api')->login($user);
        // 旧token立即失效
        // 旧token立即失效
        $apiGuard = auth('api');
        $key = sprintf('%s_%d_%s', 'user', $apiGuard->id(), 'token');
        $oldToken = Cache::get($key);
        if ($oldToken) {
            $apiGuard->setToken($oldToken);
            if ($apiGuard->check()) {
                try {
                    $apiGuard->invalidate();
                } catch (TokenExpiredException $e) {
                }
            }
        }
        Cache::put($key, $token);
        $user->ip_last = $request->ip();
        $user->login_time_last = date('Y-m-d H:i:s');
        $user->save();
        return $authorizationsController->respondWithToken($token);

    }


    private function superiorAgency($user_id, $inviter)
    {
        $agent = Agent::where('user_id', $inviter->id)->first();
        if ($agent) {
            User::where('id', '=', $user_id)->update(['admin_user_id' => $agent->admin_user_id]);
            Agent::create([
                'admin_user_id' => $agent->admin_user_id,
                'user_id' => $user_id
            ]);
        }
    }


    public function show(User $user, Request $request)
    {
        return $this->success($user);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $user->vip;
        if($user->card){
            $user->number = $user->card->number;
        }else{
            $user->number = "";
        }

        $user->currency_data = Currency::getAllCurrencies($user->currery);

        return $this->success(new UserResource($user));
    }

    public function update(UserRequest $request)
    {
        $user = $request->user();
        $attributes = $request->only(['email', 'whatsapp', 'telegram', 'whatsapp_hide', 'telegram_hide']);
        $user->update($attributes);
        $user->vip;
        return $this->success((new UserResource($user)));
    }

    public function password(PasswordRequest $request)
    {
        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            $this->errorResponse(Response::HTTP_OK, trans('error.10007'), 10007);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'p' => $request->password
        ]);

        return $this->success();
    }

    public function payPassword(PayPasswordRequest $request)
    {
        $user = $request->user();

        $user->update([
            'payment_password' => Hash::make($request->password)
        ]);

        return $this->success();
    }

    public function changePayPassword(PayPasswordRequest $request)
    {
        $user = $request->user();
        $verifyData = Cache::get($request->verification_key);
        if (!$verifyData) {
            $this->errorResponse(Response::HTTP_OK, transtable('10001'), 10001);
        }
//        if (!Hash::check($request->original_password, $user->payment_password)) {
//            return $this->fail(10007);
//        }
        if (!hash_equals($verifyData['code'], $request->verification_code)) {
            $this->errorResponse(Response::HTTP_OK, transtable('10002'), 10002);
        }

        $user->update([
            'payment_password' => Hash::make($request->password)
        ]);
        Cache::forget($request->verification_key);

        return $this->success();
    }

    public function forgetPassword(forgetPasswordRequest $request)
    {
        $verifyData = Cache::get($request->verification_key);

        if (!$verifyData) {
            $this->errorResponse(Response::HTTP_OK, transtable('10001'), 10001);
        }

        if (!hash_equals($verifyData['code'], $request->verification_code)) {
            // 返回401
            $this->errorResponse(Response::HTTP_OK, transtable('10002'), 10002);
        }

        User::where('phone', $verifyData['phone'])->update([
            'password' => Hash::make($request->password)
        ]);
        Cache::forget($request->verification_key);

        return $this->success();
    }

    public function account(IncomeRequest $request, Account $account)
    {
        $user = $request->user();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $page = $this->paging($page, $limit);
        $date = $request->date ?: '';
        $mold = $request->has('mold') && $request->get('mold') >= 0 ? $request->get('mold') : -1;
        $data = [
            'income' => (int)Account::getIncome($user->id),
            'expenditure' => (int)Account::getExpenditure($user->id),
            'list' => AccountResource::collection((new Account())->getAccounts($user->id, $request->get('type'), $mold, $page, $limit, $date))
        ];

        return $this->success($data);
    }

    //账变类型
    public function accountType()
    {
        $ids = [0,1,10,11,13,16,28,29,8];
        $data[] = ['id'=>-1,'value'=>transtable('account_type.-1')];
        foreach ($ids as $id){
            $data[] = [
                'id'=> $id,
                'value'=> transtable('account_type.'.$id)
            ];
        }
        return $this->success($data);
    }

    public function statistics(Request $request)
    {
        $user = $request->user();
        $myVip = Vip::getVip($user->vip_id);
        $vips = Vip::getVip();

        foreach ($vips as $vip) {
            $completed[] = [
                'completed' => $vip->level <= $myVip->level ? TaskLog::todayCompleted($user->id, $vip->id) : 0,
                'name' => $vip->name,
                'level' => $vip->level,
                'total' => $vip->level <= $myVip->level ? $vip->quantity : 0
            ];
        }


        $data = [
            'completed' => $completed,
            'revenue' => Account::getProfit($user->id),
            'earning' => Account::getProfit($user->id, true),
            'commission' => Account::getTeamProfit($user->id),
            'subordinate' => (new User())->countSubordinate($user->id),
        ];
        return $this->success($data);
    }


    //注册赠送不可用余额奖励详情
    public function registerReward(Request $request)
    {
        $agent_conf = AgentConf::query()->where('admin_user_id', '=', $request->user()->admin_user_id)->first();
        $completed_number = $request->user()->child(1)->count();
        $activate_number = Configuration::getItem('register_frozen_activate');
        //如果未领取的重新领取下
        if (!RegisterReward::query()->where('user_id', '=', $request->user()->id)->first()) {
            app(RegisterReward::class)->store($request->user());
        }
        return $this->success([
            'telegram_link' => $agent_conf ? $agent_conf['join_group_link'] : Configuration::getConfig($request->user(), 'join_group_link'),
            'top_content' => Configuration::getItem('register_reward_top_content'),
            'below_content' => Configuration::getItem('register_reward_below_content'),
            'reward' => RegisterReward::query()->where('user_id', '=', $request->user()->id)->value('amount'),
            'activate_number' => $activate_number,
            'completed_number' => $completed_number > $activate_number ? $activate_number : $completed_number,//一级下级人数
            'completed' => $completed_number >= $activate_number,//是否完成
            'received' => (bool)Account::query()->where('to', '=', $request->user()->id)
                ->where('type', '=', Account::REGISTER_REWARD)->count()//是否领取了奖励
        ]);
    }

    //解锁注册赠送的不可用余额
    public function unlockRegisterReward(Request $request)
    {
        $activate_number = Configuration::getItem('register_frozen_activate');
        if (!Configuration::getItem('register_frozen_reward_switch') || !Configuration::getItem('register_frozen_reward') || !$activate_number) {
            return $this->fail(70001, trans('error.70001'));
        }
        $user = $request->user();
        $completed_number = $user->child(1);
        if ($completed_number < $activate_number) {
            return $this->fail(70003, trans('error.70003'));
        }

        $res = DB::transaction(function () use ($user) {
            //发放奖励
            $register_reward = RegisterReward::query()->where('user_id', '=', $user->id)->lockForUpdate()->first();
            if ($register_reward->unlock == RegisterReward::UNLOCK_TRUE || Account::query()->where('to', '=', $user->id)->where('type', '=', Account::REGISTER_REWARD)->count()) {
                //已经解锁过奖励了
                return 70002;
            }
            $user->increment('balance', $register_reward->amount);
            $user->decrement('frozen', $register_reward->amount);
            Account::query()->create([
                'type' => Account::REGISTER_REWARD,
                'to' => $user->id,
                'from' => 0,
                'amount' => $register_reward->amount,
                'level' => 0
            ]);
            $register_reward->unlock = RegisterReward::UNLOCK_TRUE;
            $register_reward->save();
            return 1;
        }, 2);
        if ($res > 1) {
            return $this->fail($res, transtable($res));
        }
        return $this->success();
    }

}
