<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('member', function (Blueprint $table) {
            $table->id();  // 這會自動創建一個名為 id 的主鍵欄位
            $table->string('Email')->unique();
            $table->string('UserName')->unique();
            $table->string('UserPWD');
            $table->tinyInteger('Gender')->nullable();
            $table->string('Avatar')->nullable();
            $table->timestamps();  // 如果需要時間戳欄位，可以加上
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member');
    }
};
