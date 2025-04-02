<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('tbl_main_users', function (Blueprint $table) {
            // Add missing columns if they don’t already exist
            if (!Schema::hasColumn('tbl_main_users', 'id_code')) {
                $table->bigInteger('id_code')->after('id');
            }
            if (!Schema::hasColumn('tbl_main_users', 'user_rank')) {
                $table->string('user_rank', 15)->after('password');
            }
            if (!Schema::hasColumn('tbl_main_users', 'mod_access')) {
                $table->text('mod_access')->nullable()->after('user_rank');
            }
            if (!Schema::hasColumn('tbl_main_users', 'approval_access')) {
                $table->string('approval_access', 300)->default('')->after('mod_access');
            }
            if (!Schema::hasColumn('tbl_main_users', 'approval_code')) {
                $table->string('approval_code', 15)->default('')->after('approval_access');
            }
            if (!Schema::hasColumn('tbl_main_users', 'sys_type')) {
                $table->string('sys_type', 10)->after('approval_code');
            }
            if (!Schema::hasColumn('tbl_main_users', 'active_status')) {
                $table->string('active_status', 1)->after('sys_type');
            }
        });

        // Drop columns in a separate Schema::table() call
        Schema::table('tbl_main_users', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_main_users', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('tbl_main_users', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }
            if (Schema::hasColumn('tbl_main_users', 'created_at')) {
                $table->dropColumn('created_at');
            }
            if (Schema::hasColumn('tbl_main_users', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }

    public function down()
    {
        // Do nothing on rollback since you don’t want to restore dropped columns.
    }
};