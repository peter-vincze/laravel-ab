<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ab_goals', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('autocompletegoal_route_regexp_pattern');
            $table->string('goal_once_a_session');
            $table->integer('hit');
            $table->integer('experiment_id');
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
        Schema::dropIfExists('ab_goals');
    }
}
