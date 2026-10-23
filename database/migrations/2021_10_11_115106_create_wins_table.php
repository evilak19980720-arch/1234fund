<?php

use App\Models\Win;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wins', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('admin_user_id')->default(0)->comment('代理商');
            $table->string('phone')->default('')->comment('手机号');
            $table->unsignedInteger('amount')->default(0)->comment('金额');
            $table->unsignedTinyInteger('show')->default(Win::SHOW_FALSE)->comment('是否显示:0=否,1=是');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wins');
    }
}
