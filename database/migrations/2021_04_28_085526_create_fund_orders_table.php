<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFundOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fund_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fund_period_id')->default(0)->comment('排期id');
            $table->unsignedBigInteger('user_id')->default(0)->comment('用户id');
            $table->unsignedInteger('quantity')->default(0)->comment('购买数量');
            $table->unsignedInteger('amount')->default(0)->comment('金额');
            $table->boolean('win')->default(0)->comment('是否中奖:0=否,1=是');
            $table->string('buy')->default('')->comment('购买的号码数字或者big|small');
            $table->unsignedTinyInteger('status')->default(0)->comment('处理方式:0=未处理,1=饲养,2=转让');
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
        Schema::dropIfExists('fund_orders');
    }
}
