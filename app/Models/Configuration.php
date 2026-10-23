<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use App\Models\AgentConf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Models\Agent;

/**
 * 配置
 * @package App\Models
 */
class Configuration extends Model
{
    public $timestamps = false;

    public static $autotranskey = ['Invite_copy','down_title'];

    /**
     * 获取配置项
     * @param $key
     * @return mixed|null
     */
    public static function getItem($key)
    {
        $configurations = Cache::get(KeysHandler::CONFIGURATION);
        $configurations || $configurations = self::all();

        if ($configurations) {
            Cache::put(KeysHandler::CONFIGURATION,$configurations,KeysHandler::CONFIGURATION_EXPIRE);
            foreach ($configurations as $item) {
                if ($item->key === $key) {
                    return $item->value;
                }
            }
        }

        return null;
    }

    public static function getConfig($user,$key)
    {
        if (!$user) {
            $host_agent = (new AgentConf())->getAgentConf();
            if($host_agent)
            {
                return $host_agent[$key];
            }
            return self::getItem($key);
        }
        $agent = Agent::getAgent($user->id);
        if (!$agent) {
            return self::getItem($key);
        }
        $conf = AgentConf::where('admin_user_id',$agent)->first();
        return $conf->$key;
    }


}
