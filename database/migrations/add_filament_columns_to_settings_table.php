<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'label')) {
                $table->string('label')->nullable()->after('key');
            }

            if (! Schema::hasColumn('settings', 'group')) {
                $table->string('group')->default('general')->after('type');
            }

            if (! Schema::hasColumn('settings', 'type')) {
                $table->string('type')->default('text')->after('value');
            }

            if (! Schema::hasColumn('settings', 'tab_order')) {
                $table->integer('tab_order')->default(0)->after('group');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $columns = ['label', 'tab_order'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
