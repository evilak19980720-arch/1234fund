<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 站内客服（AI + 人工）
 * 单独执行：php artisan migrate --path=database/migrations/2026_10_23_000000_create_customer_service_tables.php
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('cs_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->default(0)->index()->comment('用户ID，游客为0');
            $table->string('guest_token', 64)->default('')->index()->comment('游客标识');
            $table->string('status', 16)->default('ai')->index()->comment('ai:AI接待 pending:等待人工 human:人工接待 closed:已结束');
            $table->string('transfer_reason', 255)->default('')->comment('转人工原因');
            $table->unsignedInteger('admin_user_id')->default(0)->comment('接待客服');
            $table->unsignedInteger('admin_unread')->default(0)->comment('客服未读数');
            $table->string('lang', 16)->default('');
            $table->string('ip', 64)->default('');
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('cs_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('session_id')->index();
            $table->string('role', 16)->comment('user:用户 ai:AI agent:人工客服 system:系统');
            $table->text('content');
            $table->unsignedInteger('admin_user_id')->default(0);
            $table->json('meta')->nullable()->comment('AI调用的工具、token用量等');
            $table->timestamps();
        });

        // 后台菜单：在线客服 / AI客服设置
        if (Schema::hasTable('admin_menu')) {
            $now = date('Y-m-d H:i:s');
            $order = (int) DB::table('admin_menu')->max('order');
            $parentId = DB::table('admin_menu')->insertGetId([
                'parent_id' => 0, 'order' => $order + 1, 'title' => '在线客服', 'icon' => 'fa-comments', 'uri' => '',
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('admin_menu')->insert([
                ['parent_id' => $parentId, 'order' => $order + 2, 'title' => '客服会话', 'icon' => 'fa-commenting', 'uri' => 'cs-sessions', 'created_at' => $now, 'updated_at' => $now],
                ['parent_id' => $parentId, 'order' => $order + 3, 'title' => '客服设置', 'icon' => 'fa-cog', 'uri' => 'cs-settings', 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('cs_messages');
        Schema::dropIfExists('cs_sessions');
        if (Schema::hasTable('admin_menu')) {
            DB::table('admin_menu')->whereIn('uri', ['cs-sessions', 'cs-settings'])->delete();
            DB::table('admin_menu')->where('title', '在线客服')->where('parent_id', 0)->delete();
        }
    }
};
