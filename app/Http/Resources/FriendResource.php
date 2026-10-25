<?php

namespace App\Http\Resources;

use App\Models\Configuration;
use App\Models\User;
use App\Models\Country;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class FriendResource extends JsonResource
{


    private $shuiyin = '?x-oss-process=image/watermark,size_60,text_%s,color_bbbbbb,shadow_10,t_100,rotate_0,g_se,x_15,y_15';

    public function toArray($request)
    {
        // 列表是公开接口，游客为 null
        $user = auth('api')->user();
        $userId = $user ? $user->id : 0;

        $data = parent::toArray($request);

        // 检查 user 是否存在，避免访问 null 值
        if (isset($data['user']) && $data['user'] !== null && isset($data['user']['id'])) {
            if(in_array($data['user']['id'],explode(',',Configuration::getItem('fake_friend_user_id')))){
                $icons = [3672,1785,2210,850,51,2737,731,3145,2448,204,3604,2397,2873,1496,2465,1326,1904,1615,3434,3383,578,1955,3060,425,2125,3366,935,3026,442,187,2550,3332,2618,1598,1445,1122,3689,1411,2567,2567,2720,1768,901,3536,1309,1513,884,3859,2788,2108,1870,2941,3009,3723,2992,3128,323,3247,323,833,1666,3179,2771,2363,3485,221,3621,2482,2244,799,85,119,17,1292,1564,2142,272,1258,1241,2652,561,646,2431,2516,1071,3706,2227,3553,289,3077,527,2414,34,3111,3842,867,3502,2278,1462,2312,1938,1360,357,2329,1632,1649,2278,2023,2499,136,2805,2924,1989,663,2822,2193,2890,1972,3417,952,782,2601,306,1684,1156,969,3298,340,816,3519,1054,714,3468,697,1479,3213,2040,680,1428,1819,2703,85,1717,493,1887,1734,0,3451,1921,3669,1190,323,765,3043,3094,1088,3196,2907,153,1581,2975,1173,2091,2635,2533,629,2074,3638,1037,323,3655,1377,2346,3230,3570,1003,3808,85,2295,2006,986,238,459,2584,2686,544,102,3349,1139,1836,1700,1020,1853,3587,748,85,321,511,3315,374,612,2159,1207,2958,170,2856,1343,1530,408,391,3281,1751,2176,1224,2754,2261,476,3791,1394,323,3162,2839,918,3264,595,2380,2057,1802,68,1547,3400,255,3825,3757,3740,3774,85];
                $Country = Country::getCountries()->toArray();
                $data['user']['telegram_hide'] = 1;
                $data['user']['whatsapp_hide'] = 1;
                $data['user']['name'] = $Country[array_rand($Country)]['area']. rand(1000000000,9999999999);
                $data['user']['countries']['icon'] = $Country[array_rand($Country)]['icon'];
                $data['user']['countries']['icon'] = "2363"; //孟加拉
                $data['user']['vip']['name'] = 'VIP'.rand(1,6);
            }
        }

        //print_r($data);exit;
        //$data['created_at'] = $this->resource->created_at->diffForHumans();

        if ($data['imgs']) {
            $data['imgs'] = array_map(function ($var) {

                $this->shuiyin = sprintf($this->shuiyin,config('app.friend_shuiyin'));
                $var['img'] = sprintf('%s/%s', aliyun_domain(), $var['img']. $this->shuiyin);
                //$var['img'] = sprintf('%s/%s', aliyun_domain(), $var['img']);
                return $var;
            }, $data['imgs']);
        }
        $data['mine'] = $userId > 0 && $data['user_id'] == $userId;

        unset($data['user_id']);
        if (isset($data['updated_at']) && $data['updated_at']) {
            unset($data['updated_at']);
        }

        $up = isset($data['up']) ? $data['up'] : '';
        $users = array_filter(explode(',', $up),function ($var) {
            return $var;
        });
        $times = isset($data['times']) ? $data['times'] : 0;
        $data['up'] = count($users)+$times;
        $data['thumbs_up'] = $userId > 0 && in_array($userId, $users);

        unset($data['times']);

        //隐藏号码
        //if($data['user']['hidephone'] == 1){
        if(isset($data['user']) && $data['user'] !== null && isset($data['user']['whatsapp_hide']) && ($data['user']['whatsapp_hide'] == 1 || $data['mine']==true)){
            if(isset($data['user']['name'])){
                $data['user']['name'] = sprintf('%s****%s', substr($data['user']['name'], 0, 3), substr($data['user']['name'], -3));
            }
        }


        $data['link'] = $data['telegram_link'] = $data['whatsapp_link'] = '';

        //TG链接
        if(isset($data['user']) && $data['user'] !== null && isset($data['user']['telegram_hide']) && $data['user']['telegram_hide'] == 0 && $data['mine']==false && !empty($data['user']['telegram'])){
            if(strpos($data['user']['telegram'], 'http') === 0){
                $data['telegram_link'] =  $data['user']['telegram'];
            }else{
                $telegram = ltrim($data['user']['telegram'], '@');
                $data['telegram_link'] = sprintf('%s%s',User::TELEGRAM_URL,$telegram);
            }

        }

        //WS链接
        if(isset($data['user']) && $data['user'] !== null && isset($data['user']['whatsapp_hide']) && $data['user']['whatsapp_hide'] == 0 && $data['mine']==false && !empty($data['user']['whatsapp'])){
            $data['whatsapp_link'] = sprintf('%s=%s',User::WHATAPP_URL,$data['user']['whatsapp']);
        }

        //号码链接
        if(isset($data['user']) && $data['user'] !== null && isset($data['user']['telegram_hide']) && $data['user']['telegram_hide'] == 0 && $data['mine']==false){
            $data['link'] = $data['telegram_link'];
        }

        // 接口公开后，不返回原始的 WhatsApp / Telegram 字段（页面只用上面生成的链接，用户隐藏了的就不会有链接）
        if(isset($data['user']) && is_array($data['user'])){
            unset($data['user']['whatsapp'], $data['user']['telegram']);
            // 游客看到的用户名（手机号）一律打码，防止被批量抓取
            if(!$user && isset($data['user']['name'])){
                $data['user']['name'] = sprintf('%s****%s', substr($data['user']['name'], 0, 3), substr($data['user']['name'], -3));
            }
        }

        return $data;
    }
}
