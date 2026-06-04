<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('job_timeline', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['job_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_timeline');
    }
};
