<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Sql
{
    public static function call(string $name, array $args = []): array
    {
        // Routine names are constants in application code, never request input.
        $statement = DB::connection()->getPdo()->prepare('CALL ' . $name . '(' . implode(',', array_fill(0, count($args), '?')) . ')');
        try {
            $statement->execute($args);
            $rows = $statement->fetchAll(\PDO::FETCH_OBJ);
            while ($statement->nextRowset()) {
            }

            return $rows;
        } finally {
            $statement->closeCursor();
        }
    }

    public static function locked(\Closure $action): mixed
    {
        return DB::transaction(function () use ($action) {
            DB::select('SELECT id FROM parking_operation_lock WHERE id=1 FOR UPDATE');

            return $action();
        }, 3);
    }
}
