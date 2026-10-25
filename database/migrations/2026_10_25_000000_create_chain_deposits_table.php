<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 链上充值处理记录：每笔链上交易只处理一次，防止并发查询重复入账
 * 单独执行：php artisan migrate --path=database/migrations/2026_10_25_000000_create_chain_deposits_table.php
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('chain_deposits', function (Blueprint $table) {
            $table->increments('id');
            $table->string('tx_id', 100)->unique()->comment('链上交易哈希');
            $table->string('currery', 20)->comment('USDT-TRC / USDT-BSC / TRX');
            $table->unsignedInteger('user_id')->index();
            $table->unsignedInteger('order_id')->nullable()->comment('对应的充值订单');
            $table->bigInteger('amount')->default(0)->comment('金额（分）');
            $table->string('status', 20)->comment('credited 已入账 / below_min 低于最低充值金额 / existing 改造前已入账');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chain_deposits');
    }
};
