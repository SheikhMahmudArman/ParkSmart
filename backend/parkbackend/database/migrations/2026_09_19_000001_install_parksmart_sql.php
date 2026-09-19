<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('ParkSmart requires MySQL.');
        }
        $delimiter = ';';
        $buffer = '';
        foreach (file(database_path('sql/parksmart.sql')) as $line) {
            if (preg_match('/^DELIMITER\s+(\S+)/i', trim($line), $match)) {
                $delimiter = $match[1];

                continue;
            }
            if (str_starts_with(trim($line), '--') || trim($line) === '') {
                continue;
            }
            $buffer .= $line;
            if (str_ends_with(rtrim($buffer), $delimiter)) {
                DB::unprepared(substr(rtrim($buffer), 0, -strlen($delimiter)));
                $buffer = '';
            }
        }
    }

    public function down(): void
    {
        foreach (['sp_reserve', 'sp_reservation_status', 'sp_pay_reservation', 'sp_entry', 'sp_exit', 'sp_pay_fine', 'sp_create_lot', 'sp_expire_reservations'] as $name) {
            DB::unprepared("DROP PROCEDURE IF EXISTS $name");
        }
        foreach (['reservations_validate_insert', 'reservations_audit_insert', 'reservations_audit_update'] as $name) {
            DB::unprepared("DROP TRIGGER IF EXISTS $name");
        }
        foreach (['v_lot_availability', 'v_payment_details', 'v_revenue_by_lot'] as $name) {
            DB::unprepared("DROP VIEW IF EXISTS $name");
        }
        DB::unprepared('DROP FUNCTION IF EXISTS fn_parking_price');
        DB::unprepared('DROP TABLE IF EXISTS reservation_audit');
        DB::unprepared('DROP TABLE IF EXISTS parking_operation_lock');
    }
};
