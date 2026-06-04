<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('job_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->onDelete('cascade');
            $table->enum('type', [
                'email', 'note', 'status_change', 'assignment', 
                'completion', 'payment', 'attachment', 'created', 
                'updated', 'deleted', 'task_added', 'task_updated', 
                'task_deleted', 'attachment_added', 'attachment_deleted', 'other'
            ]);
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
            $table->json('metadata')->nullable(); // For storing email recipients, etc.

            // Performed by
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();

            // BaseModel fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            $table->index(['job_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_activities');
    }
};
