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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name', 70);
            $table->string('subdomain', 100);

            // configurasi project
            $table->string('public_root');
            $table->integer('port')->nullable();
            $table->string('path');
            $table->string('repository_url', 255);
            $table->tinyInteger('mode')->default(0);

            // configrasi database
            $table->string('dbuser');
            $table->string('dbname');
            $table->string('dbpassword');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
