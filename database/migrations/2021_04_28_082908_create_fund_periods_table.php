<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFundPeriodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        //新彩种排期表
        Schema::create('fund_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fund_id')->default(0)->comment('新彩种id');
            $table->unsignedBigInteger('pet_id')->default(0)->comment('宠物id');
            $table->unsignedInteger('profit')->default(0)->comment('宠物收益');
            $table->unsignedInteger('day')->default(0)->comment('宠物收益周期');
            $table->unsignedBigInteger('admin_user_id')->default(0)->comment('代理商id');
            $table->unsignedInteger('total_price')->default(0)->comment('总价');
            $table->string('issue')->default('')->comment('期号');
            $table->timestamp('start_time')->nullable()->comment('开始时间');
            $table->timestamp('end_time')->nullable()->comment('开始时间');
            $table->boolean('draw')->default(0)->comment('是否开奖:0=否,1=是');
            $table->unsignedTinyInteger('draw_pattern')->default(0)->comment('开奖模式:0=随机开奖,1=系统指定');
            $table->tinyInteger('draw_number')->default(-1)->comment('开奖数字,默认-1未开奖');
            $table->enum('draw_size',['no','big','small'])->default('no')->comment('开奖大小,默认no未开奖');
            $table->unsignedInteger('win_number')->default(0)->comment('中奖人数,随机生成的');
            $table->unsignedTinyInteger('sort')->default(0)->comment('排序');
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
        Schema::dropIfExists('fund_periods');
    }
}
