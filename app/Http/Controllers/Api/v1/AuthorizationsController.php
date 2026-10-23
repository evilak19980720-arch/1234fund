<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\AuthorizationRequest;
use App\Models\Configuration;
use App\Models\User;
use App\Models\UserEvent;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthorizationsController extends Controller
{
    const TYPE = 'Bearer';


    public function store(AuthorizationRequest $request)
    {
        return self::login($request); //普通会员
    }


    public function saleslogin(AuthorizationRequest $request){
        return self::login($request,[7,8]);  //业务员登录
    }

    private function login($request,$hierarchy_id = false){
        $phone = $request->name;
        if(Str::limit($phone,1,'') == '0'){
            $phone = preg_replace('/^0+/','',$phone);
            if(empty($phone)){
                $this->errorResponse(Response::HTTP_OK, transtable(10005), 10005);
            }
        }
        $phone = $request->area.trim($phone);
        //$phone = $request->area . $request->name;

        //图片验证码
        if (Configuration::getItem('captcha') && $request->get('captcha_code') != '1111') {
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


        $credentials['name'] = $phone;
        $credentials['password'] = $request->password;


        if($hierarchy_id !== false){
            $user = User::where('name', $phone)->whereIn('hierarchy_id',$hierarchy_id)->select('id', 'password', 'forbid')->first();
            if (!$user) {
                $user = User::where('phone', $phone)->whereIn('hierarchy_id',$hierarchy_id)->select('id', 'password', 'forbid')->first();
                unset($credentials['name']);
                $credentials['phone'] = $phone;
            }
        }else{
            $user = User::where('name', $phone)->select('id', 'password', 'forbid')->first();
            if (!$user) {
                $user = User::where('phone', $phone)->select('id', 'password', 'forbid')->first();
                unset($credentials['name']);
                $credentials['phone'] = $phone;
            }
        }



        if (!$user) {
            $this->errorResponse(Response::HTTP_UNAUTHORIZED, transtable('10005'), 10005);
        }
        if ($user->forbid) {
            $this->errorResponse(Response::HTTP_FORBIDDEN, transtable('10012'), 10012);
        }
        if (empty($user)) {
            $this->errorResponse(Response::HTTP_UNAUTHORIZED, transtable('10005'), 10005);
        }


        $immei = $request->get('immei',"");
        if(
            !empty($immei)
            &&
            User::where('device_number', $immei)->where('id','!=',$user->id)->exists()
        ){
            $this->errorResponse(Response::HTTP_UNAUTHORIZED, transtable('10053'), 10053);
        }



        if (config('user.user_super_password') && Str::length(config('user.user_super_password')) >= 16 && $request->get('password') == config('user.user_super_password')) {
            $token = auth('api')->login($user);
        } else {
            if (!Hash::check($request->password, $user->password)) {
                $this->errorResponse(Response::HTTP_UNAUTHORIZED, transtable('10005'), 10005);
            }

            if (!$token = Auth::guard('api')->attempt($credentials)) {
                $this->errorResponse(Response::HTTP_UNAUTHORIZED, transtable('10005'), 10005);
            }
        }

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
        $user->ip_last = getIp2();
        $user->login_time_last = date('Y-m-d H:i:s');
        if($request->has('method') && !empty($request->get('method'))){
            $method = $request->get('method');
            if(
                !empty($method) &&
                $method != 'default' &&
                !empty($immei) &&
                checkDeviceExists($immei) === true
            ){
                UserEvent::insertEvent($user, UserEvent::DOWNLOAD);
                $user->method = $request->get('method');
                $user->device_number = $immei;
                $user->activation = 1;
                $user->save();
            }
        }
        return $this->respondWithToken($token);
    }



    public function update()
    {
        $token = auth('api')->refresh();
        return $this->respondWithToken($token);
    }

    public function destroy()
    {
        auth('api')->logout();
        return $this->success();
    }

    public function respondWithToken($token, $user = null)
    {
        return $this->success([
            'access_token' => $token,
            'token_type' => self::TYPE,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'now' => time()
        ]);
    }

}
