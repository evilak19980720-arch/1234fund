<?php

use App\Models\RegisterReward;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegisterRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('register_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->default(0)->comment('用户id');
            $table->unsignedInteger('amount')->default(0)->comment('金额');
            $table->boolean('unlock')->default(RegisterReward::UNLOCK_FALSE)->comment('解锁:0=否,1=是');
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
        Schema::dropIfExists('register_rewards');
    }
}
