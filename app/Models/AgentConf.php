<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Env;

class AgentConf extends Model
{
    use SoftDeletes;

    protected $table = 'agent_conf';

    public function user()
    {
        return $this->belongsTo(AdminUser::class);
    }

    static public function isAgent()
    {
        $host = request()->server('HTTP_ORIGIN');
        $users = self::select('id','admin_user_id','domain')->get()->toArray();
        if (!$users || !$host) {
            return false;
        }

        $domains = [];
        foreach ($users as $val){
            $this_domains = explode('|',$val['domain']);
            foreach ($this_domains as $domain){
                $arr = [
                    'admin_user_id' => $val['admin_user_id'],
                    'domain' =>$domain
                ];
                $domains[] = $arr;
            }
        }

        $domains = array_filter($domains, function ($var) use($host) {
            if ($var['domain']) {
                return strpos(GetUrlToDomain($var['domain']),GetUrlToDomain($host)) !== false;
            }
        });
        $domains = array_values($domains);

        if ($domains) {
            return self::where('admin_user_id','=',$domains[0]['admin_user_id'])->first();
        }
        return false;
    }

    public function getH5Url($user)
    {
        $url = Env::get('H5URL');
        if($user){
            $agent = Agent::getAgent($user->id);
            if($agent != 0){
                $agentinfo = self::where('admin_user_id','=',$agent)->first();
                $url = $agentinfo->domain;
            }
        }else{
            $agent_conf = $this->getAgentConf();
            if ($agent_conf)
            {
                $url = $agent_conf['domain'];
            }
        }
        return $url;
    }


    /**
     * 根据请求客户端获取代理商配置
     * @return array|bool
     */
    public function getAgentConf()
    {
        $host = request()->server('HTTP_ORIGIN');
        $users = self::query()->get()->toArray();
        if (!$users || !$host)
        {
            return false;
        }
        $agent_conf = [];
        foreach ($users as $key => $val)
        {
            $this_domains = explode('|',$val['domain']);
            foreach($this_domains as $item)
            {
                if (GetUrlToDomain($item) == GetUrlToDomain($host))
                {
                    $agent_conf = $val;
                    $agent_conf['domain'] = $item;
                    break;
                }
            }
            if ($agent_conf)
            {
                break;
            }
        }
        return $agent_conf ?: false;
    }

}
