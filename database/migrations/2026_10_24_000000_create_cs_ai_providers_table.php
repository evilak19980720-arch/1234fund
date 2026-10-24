<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 客服 AI 服务商配置
 * 单独执行：php artisan migrate --path=database/migrations/2026_10_24_000000_create_cs_ai_providers_table.php
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('cs_ai_providers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('provider', 32)->unique()->comment('服务商标识，见 AiProviders::LIST');
            $table->text('api_key')->nullable()->comment('API Key（加密存储）');
            $table->string('model', 128)->default('');
            $table->string('base_url', 255)->default('');
            $table->timestamps();
        });

        // AI 提示词存放在 configurations.value，原来是 varchar 时改成 TEXT 以便存长文本
        $column = DB::selectOne(
            'SELECT DATA_TYPE AS type FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getTablePrefix() . 'configurations', 'value']
        );
        if ($column && in_array(strtolower($column->type), ['varchar', 'char'], true)) {
            DB::statement('ALTER TABLE `' . DB::getTablePrefix() . 'configurations` MODIFY `value` TEXT NULL');
        }
    }

    public function down()
    {
        Schema::dropIfExists('cs_ai_providers');
    }
};
