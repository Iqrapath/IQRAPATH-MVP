<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: use WHEN clause, no IF blocks; split update into two triggers

            // INSERT trigger
            DB::unprepared('
                CREATE TRIGGER update_guardian_children_count_after_insert
                AFTER INSERT ON student_profiles
                FOR EACH ROW
                WHEN NEW.guardian_id IS NOT NULL
                BEGIN
                    UPDATE guardian_profiles
                    SET children_count = (
                        SELECT COUNT(*)
                        FROM student_profiles
                        WHERE guardian_id = NEW.guardian_id
                    )
                    WHERE user_id = NEW.guardian_id;
                END
            ');

            // UPDATE trigger for OLD.guardian_id
            DB::unprepared('
                CREATE TRIGGER update_guardian_children_count_after_update_old
                AFTER UPDATE ON student_profiles
                FOR EACH ROW
                WHEN OLD.guardian_id IS NOT NULL
                BEGIN
                    UPDATE guardian_profiles
                    SET children_count = (
                        SELECT COUNT(*)
                        FROM student_profiles
                        WHERE guardian_id = OLD.guardian_id
                    )
                    WHERE user_id = OLD.guardian_id;
                END
            ');

            // UPDATE trigger for NEW.guardian_id (if changed)
            DB::unprepared('
                CREATE TRIGGER update_guardian_children_count_after_update_new
                AFTER UPDATE ON student_profiles
                FOR EACH ROW
                WHEN NEW.guardian_id IS NOT NULL AND NEW.guardian_id != OLD.guardian_id
                BEGIN
                    UPDATE guardian_profiles
                    SET children_count = (
                        SELECT COUNT(*)
                        FROM student_profiles
                        WHERE guardian_id = NEW.guardian_id
                    )
                    WHERE user_id = NEW.guardian_id;
                END
            ');

            // DELETE trigger
            DB::unprepared('
                CREATE TRIGGER update_guardian_children_count_after_delete
                AFTER DELETE ON student_profiles
                FOR EACH ROW
                WHEN OLD.guardian_id IS NOT NULL
                BEGIN
                    UPDATE guardian_profiles
                    SET children_count = (
                        SELECT COUNT(*)
                        FROM student_profiles
                        WHERE guardian_id = OLD.guardian_id
                    )
                    WHERE user_id = OLD.guardian_id;
                END
            ');
        } else {
            // Default to MySQL/MariaDB: use IF ... THEN ... END IF; blocks

            // Create trigger for insert operations
            DB::unprepared('
                CREATE TRIGGER update_guardian_children_count_after_insert
                AFTER INSERT ON student_profiles
                FOR EACH ROW
                BEGIN
                    IF NEW.guardian_id IS NOT NULL THEN
                        UPDATE guardian_profiles
                        SET children_count = (
                            SELECT COUNT(*) 
                            FROM student_profiles 
                            WHERE guardian_id = NEW.guardian_id
                        )
                        WHERE user_id = NEW.guardian_id;
                    END IF;
                END
            ');

            // Create trigger for update operations
            DB::unprepared('
                CREATE TRIGGER update_guardian_children_count_after_update
                AFTER UPDATE ON student_profiles
                FOR EACH ROW
                BEGIN
                    IF OLD.guardian_id IS NOT NULL THEN
                        UPDATE guardian_profiles
                        SET children_count = (
                            SELECT COUNT(*) 
                            FROM student_profiles 
                            WHERE guardian_id = OLD.guardian_id
                        )
                        WHERE user_id = OLD.guardian_id;
                    END IF;
                    
                    IF NEW.guardian_id IS NOT NULL AND NEW.guardian_id != OLD.guardian_id THEN
                        UPDATE guardian_profiles
                        SET children_count = (
                            SELECT COUNT(*) 
                            FROM student_profiles 
                            WHERE guardian_id = NEW.guardian_id
                        )
                        WHERE user_id = NEW.guardian_id;
                    END IF;
                END
            ');

            // Create trigger for delete operations
            DB::unprepared('
                CREATE TRIGGER update_guardian_children_count_after_delete
                AFTER DELETE ON student_profiles
                FOR EACH ROW
                BEGIN
                    IF OLD.guardian_id IS NOT NULL THEN
                        UPDATE guardian_profiles
                        SET children_count = (
                            SELECT COUNT(*) 
                            FROM student_profiles 
                            WHERE guardian_id = OLD.guardian_id
                        )
                        WHERE user_id = OLD.guardian_id;
                    END IF;
                END
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all variants to support both drivers
        DB::unprepared('DROP TRIGGER IF EXISTS update_guardian_children_count_after_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS update_guardian_children_count_after_update');
        DB::unprepared('DROP TRIGGER IF EXISTS update_guardian_children_count_after_update_old');
        DB::unprepared('DROP TRIGGER IF EXISTS update_guardian_children_count_after_update_new');
        DB::unprepared('DROP TRIGGER IF EXISTS update_guardian_children_count_after_delete');
    }
}; 