<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agar "settings" table already hai
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                // Yaha par apne naye columns add karna
                // Example: extra_config
                if (!Schema::hasColumn('settings', 'extra_config')) {
                    $table->text('extra_config')->nullable();
                }
            });
        } 
        // Agar "settings" table nahi hai
        else {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Agar humne "extra_config" column add kiya hai to wapas hata dena
        if (Schema::hasColumn('settings', 'extra_config')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('extra_config');
            });
        }
    }
};