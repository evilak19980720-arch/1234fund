<?php

namespace App\Http\Resources;

use App\Models\Configuration;
use App\Models\Red;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class RedResource extends JsonResource
{
    public function toArray($request)
    {
        if ($this->resource) {
            $user = null;
            $authorization = $request->header('authorization');
            if ($authorization) {
                try {
                    $user = JWTAuth::parseToken()->touser();
                } catch (JWTException $exception) {
                    $exception->getMessage();
                }
            }

            $data = parent::toArray($request);
            //$data['now_time'] = time();
            //$data['invite_total'] = intval(time() % 10000) + (date('m') - 1) * 110000 + date('y') * 3600;
            //$data['red_total'] = intval(time() / 1000) * 100 * $data['level'];
            if (isset($data['red_distribution'])) {
                unset($data['red_distribution']);
            }
            //$data['WHATAPP'] = 'https://api.whatsapp.com/send?phone=';
            //$data['TELEGRAM'] = 'https://t.me/@';
            //$data['Instagram'] = 'http://instagram.com/';
            //$data['Facebook'] = 'https://www.facebook.com/';
            //$data['invite_desc'] = Configuration::getItem('invite_desc');
            //$data['join_group_str'] = Configuration::getConfig($user,'join_group_str');
            //$data['join_group_link'] = Configuration::getConfig($user,'join_group_link');
            return $data;
        }
    }
}
