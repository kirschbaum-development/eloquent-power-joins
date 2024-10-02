<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->boolean('rockstar')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->timestamps();
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('access_level')->default(0);
            $table->timestamps();
        });

        Schema::create('group_parent', function (Blueprint $table) {
            $table->integer('group_id');
            $table->integer('parent_group_id');
        });

        Schema::create('group_members', function (Blueprint $table) {
            $table->integer('group_id')->unsigned();
            $table->integer('user_id')->unsigned();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('category_id');
            $table->string('title');
            $table->boolean('reviewed')->default(true);
            $table->boolean('published')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('post_groups', function (Blueprint $table) {
            $table->unsignedInteger('group_id');
            $table->unsignedInteger('post_id');
            $table->timestamp('assigned_at')->nullable()->default(null);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->nullable()->default(null);
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('post_id');
            $table->text('body');
            $table->integer('votes')->default(0);
            $table->boolean('approved')->default(1);
            $table->timestamps();
        });

        Schema::create('images', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('imageable');
            $table->boolean('cover')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id');
            $table->morphs('taggable');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('posts');
    }
}
