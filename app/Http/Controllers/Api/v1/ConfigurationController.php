<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Configuration;
use Illuminate\Support\Env;
class ConfigurationController extends Controller
{


    public function index()
    {
        $keys = ['withdrawal_fee','aboutusurl'];
        $data = [];
        foreach ($keys as $key){
            $data[$key] = Configuration::getItem($key);
        }
        return $this->success($data);
    }


}
