<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('universities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->timestamps();
        });

        // update users: tambah university_id
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('university_id')->nullable()->after('role');
            $table->foreign('university_id')->references('id')->on('universities')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['university_id']);
            $table->dropColumn('university_id');
        });
        Schema::dropIfExists('universities');
    }
};
