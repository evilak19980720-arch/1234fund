<?php

namespace App\Http\Resources;

use App\Models\ChinaArea;
use App\Models\Configuration;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Env;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        $this->resource->makeHidden('relation', 'updated_at', 'vip_id','p')->toArray();

        $data = parent::toArray($request);
        isset($data['created_at']) && $data['created_at'] = $this->resource->created_at->timestamp;
        if (isset($data['card'])) {
            unset($data['card']);
        }
        if (isset($data['vip']['cost_price'])) {
            unset($data['vip']['cost_price']);
        }
        if (isset($data['vip']['discount_price'])) {
            unset($data['vip']['discount_price']);
        }
        if (isset($data['vip']['salary'])) {
            unset($data['vip']['salary']);
        }
        if (isset($data['vip']['quantity'])) {
            unset($data['vip']['quantity']);
        }
        if (isset($data['vip']['state'])) {
            unset($data['vip']['state']);
        }
        if (isset($data['vip']['recharge_amount'])) {
            unset($data['vip']['recharge_amount']);
        }
        //if (isset($data['hierarchy_id'])) {
        //    unset($data['hierarchy_id']);
        //}
        if (isset($data['payment_password']) && $data['payment_password']) {
            $data['payment_password'] = true;
        } else {
            $data['payment_password'] = false;
        }
        if (empty($this->resource->relation)) {
            $data['parent_id'] = 0;
        } else {
            $user_ids = explode(',', trim($this->resource->relation, ','));
            $user_ids = array_reverse($user_ids);
            $data['parent_id'] = $user_ids[0];
        }
        $data['invite_code'] = $data['id'];
        //$data['invite_url'] = Env::get('H5URL') . '/register?invite_code=' . $data['invite_code'];
        $data['invite_url'] = '/register?invite_code=' . $data['invite_code'];
        // 后台配置了分享域名就由后端随机取一个，放在 share_domains 里只返回这一个（前端用它拼 invite_url）；
        // 没配置返回空数组，前端用当前域名
        $domains = array_values(array_filter(array_map(function ($domain) {
            return rtrim(trim($domain), '/');
        }, preg_split('/[,，;；\s]+/u', (string) Configuration::getItem('share_domains')))));
        $data['share_domains'] = [];
        if ($domains) {
            $domain = $domains[array_rand($domains)];
            // 后台只填了 a.com 时补上 https://，前端只认 http(s):// 开头的域名
            if (!preg_match('#^https?://#i', $domain)) {
                $domain = 'https://' . $domain;
            }
            $data['share_domains'] = [$domain];
        }
        $data['invite_desc'] = transtableadmin('configuration.Invite_copy');//  Configuration::getItem('');
        $data['avatar'] = $data['avatar'] ? sprintf('%s/%s', aliyun_domain(), $data['avatar']) : '';
        $data['friends_reward'] = Configuration::getItem('friends_reward');
        $data['join_group_link'] = Configuration::getItem('join_group_link');
        return $data;
    }
}
