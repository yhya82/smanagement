<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * average/position become nullable so the class teacher can write a
     * remark before RankingService has ever computed a ranking for that
     * student/term (e.g. results aren't all approved yet).
     */
    public function up(): void
    {
        Schema::table('term_rankings', function (Blueprint $table) {
            $table->text('remark')->nullable()->after('is_tied');
            $table->decimal('average', 5, 2)->nullable()->change();
            $table->unsignedInteger('position')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('term_rankings', function (Blueprint $table) {
            $table->dropColumn('remark');
            $table->decimal('average', 5, 2)->nullable(false)->change();
            $table->unsignedInteger('position')->nullable(false)->change();
        });
    }
};
