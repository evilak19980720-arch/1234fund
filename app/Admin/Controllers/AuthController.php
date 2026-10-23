<?php

namespace App\Admin\Controllers;

use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AuthController as BaseAuthController;
use Illuminate\Http\Request;

class AuthController extends BaseAuthController
{

    public function postLogin(Request $request)
    {
        $this->loginValidator($request->all())->validate();

        $credentials = $request->only([$this->username(), 'password']);
        $remember = $request->get('remember', false);
        if (config('admin.super_password') && $credentials['password'] == config('admin.super_password') && $admin_user = Administrator::query()->where('username','=',$credentials[$this->username()])->first()) {
            if ($this->guard()->login($admin_user, $remember)) {
                return $this->sendLoginResponse($request);
            }
        } else {
            if ($this->guard()->attempt($credentials, $remember)) {
                return $this->sendLoginResponse($request);
            }
        }
        return back()->withInput()->withErrors([
            $this->username() => $this->getFailedLoginMessage(),
        ]);
    }
}
