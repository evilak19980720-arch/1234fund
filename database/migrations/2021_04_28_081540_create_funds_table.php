<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //新彩种配置
        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pet_id')->default(0)->comment('宠物id');
            $table->unsignedBigInteger('admin_user_id')->default(0)->comment('代理商id');
            $table->time('start')->comment('开始时间');
            $table->time('end')->comment('结束时间');
            $table->mediumInteger('interval')->comment(5)->comment('间隔');
            $table->boolean('state')->default(0)->comment('开关');
            $table->unsignedTinyInteger('rate')->default(0)->comment('转让费率');
            $table->unsignedTinyInteger('sort')->default(0)->comment('排序');
            $table->text('desc')->comment('描述');
            $table->softDeletes();
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
        Schema::dropIfExists('funds');
    }
}
