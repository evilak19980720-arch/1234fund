<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Handlers\KeysHandler;

/**
 * 分享链接域名：后台「配置」里填多个域名（逗号分隔），前台随机取一个拼接邀请链接
 */
return new class extends Migration
{
    public function up()
    {
        if (!DB::table('configurations')->where('key', 'share_domains')->exists()) {
            DB::table('configurations')->insert([
                'type' => 'array',
                'key' => 'share_domains',
                'value' => '',
                'describe' => '分享链接域名，多个用逗号分隔，如 https://a.com,https://b.com；留空则用当前访问域名',
            ]);
            Cache::forget(KeysHandler::CONFIGURATION);
        }
    }

    public function down()
    {
        DB::table('configurations')->where('key', 'share_domains')->delete();
        Cache::forget(KeysHandler::CONFIGURATION);
    }
};
