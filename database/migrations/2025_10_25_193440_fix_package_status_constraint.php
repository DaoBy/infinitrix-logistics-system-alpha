<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // Use raw SQL to handle this safely
            DB::statement('PRAGMA foreign_keys = OFF');
            
            // 1. Rename current table
            DB::statement('ALTER TABLE package_status_histories RENAME TO package_status_histories_old');
            
            // 2. Create new table without constraints
            DB::statement('
                CREATE TABLE package_status_histories (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    package_id INTEGER NOT NULL,
                    status TEXT NOT NULL,
                    updated_by INTEGER,
                    remarks TEXT,
                    package_transfer_id INTEGER,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP
                )
            ');
            
            // 3. Copy all data
            DB::statement('
                INSERT INTO package_status_histories 
                SELECT * FROM package_status_histories_old
            ');
            
            // 4. Drop old table
            DB::statement('DROP TABLE package_status_histories_old');
            
            // 5. Re-enable foreign keys
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    public function down()
    {
        // Cannot easily revert
    }
};