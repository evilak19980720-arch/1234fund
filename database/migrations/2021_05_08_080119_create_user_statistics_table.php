<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserStatisticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->default(0)->comment('会员id');
            $table->string('phone',30)->default('')->comment('电话号码');
            $table->unsignedInteger('recharge')->default(0)->comment('充值金额');
            $table->unsignedInteger('today_recharge')->default(0)->comment('今日充值金额');
            $table->unsignedInteger('commission')->default(0)->comment('返佣金额');
            $table->unsignedInteger('one_level')->default(0)->comment('一级人数');
            $table->unsignedInteger('two_level')->default(0)->comment('二级人数');
            $table->unsignedInteger('three_level')->default(0)->comment('三级人数');
            $table->unsignedInteger('subordinate_total')->default(0)->comment('下级总数');
            $table->unsignedInteger('subordinate_recharge')->default(0)->comment('下级充值总额');
            $table->unsignedInteger('subordinate_today_recharge')->default(0)->comment('下级今日充值总额');
            $table->timestamp('register_time')->nullable()->comment('注册时间');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_statistics');
    }
}
