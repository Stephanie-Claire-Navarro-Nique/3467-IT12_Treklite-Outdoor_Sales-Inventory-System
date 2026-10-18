<?php

use App\Support\TableUpgrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $isNew = ! Schema::hasTable('image') && ! Schema::hasTable('images');

        TableUpgrade::create('image', 'images', function (Blueprint $table) {
            $table->id();
            $table->string('file_name')->nullable();
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size');
            $table->binary('image_data');                          // the photo itself, kept inside the database
            $table->timestamps();
        });

        // A plain BLOB holds only 64 KB on MySQL; MEDIUMBLOB holds up to 16 MB.
        if ($isNew && in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE image MODIFY image_data MEDIUMBLOB NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('image');
    }
};
