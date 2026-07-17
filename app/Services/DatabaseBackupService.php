<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DatabaseBackupService
{
    /**
     * Backup seluruh database ke file .sql murni via PHP (tanpa bergantung
     * pada binary `mysqldump`, agar tetap berjalan di semua environment hosting).
     */
    public function backup(): string
    {
        $database = config('database.connections.mysql.database');
        $tables = collect(DB::select('SHOW TABLES'))->map(fn ($row) => array_values((array) $row)[0]);

        $sql = "-- Molly Fantasy Database Backup\n";
        $sql .= "-- Database: {$database}\n";
        $sql .= '-- Generated at: '.now()->toDateTimeString()."\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $createStatement = DB::select("SHOW CREATE TABLE `{$table}`")[0]->{'Create Table'};

            $sql .= "-- --------------------------------------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createStatement.";\n\n";

            $rows = DB::table($table)->get();

            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $columns = implode('`, `', array_keys($rowArray));
                $values = implode(', ', array_map(function ($value) {
                    if (is_null($value)) {
                        return 'NULL';
                    }

                    return DB::connection()->getPdo()->quote((string) $value);
                }, array_values($rowArray)));

                $sql .= "INSERT INTO `{$table}` (`{$columns}`) VALUES ({$values});\n";
            }

            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $filename = 'backups/backup-'.now()->format('Ymd-His').'.sql';
        Storage::disk('local')->put($filename, $sql);

        return $filename;
    }

    /**
     * Restore database dari file .sql yang diupload. Menjalankan statement
     * satu per satu di dalam transaksi.
     */
    public function restore(string $sqlContent): void
    {
        DB::unprepared('SET FOREIGN_KEY_CHECKS=0');

        $statements = array_filter(array_map('trim', explode(";\n", $sqlContent)));

        foreach ($statements as $statement) {
            if ($statement === '' || str_starts_with($statement, '--')) {
                continue;
            }

            DB::unprepared($statement);
        }

        DB::unprepared('SET FOREIGN_KEY_CHECKS=1');
    }

    public function listBackups(): array
    {
        if (! Storage::disk('local')->exists('backups')) {
            return [];
        }

        return collect(Storage::disk('local')->files('backups'))
            ->filter(fn ($f) => str_ends_with($f, '.sql'))
            ->sortDesc()
            ->map(fn ($f) => [
                'path' => $f,
                'name' => basename($f),
                'size' => Storage::disk('local')->size($f),
                'modified' => \Carbon\Carbon::createFromTimestamp(Storage::disk('local')->lastModified($f)),
            ])
            ->values()
            ->all();
    }
}
