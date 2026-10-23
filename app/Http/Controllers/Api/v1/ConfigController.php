<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\ConfigRequest;
use Illuminate\Support\Env;

class ConfigController extends Controller
{
    // 验证码开关
    public function show(ConfigRequest $request)
    {
        $value = Env::get(strtoupper($request->name));
        return $this->success([
            'value' => $value
        ]);
    }
}
