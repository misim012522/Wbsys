<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanCentralSchema extends Command
{
    protected $signature = 'central:clean-schema';

    protected $description = 'Drop unused tables from the central database and keep only the central app tables.';

    public function handle(): int
    {
        $connection = DB::connection('central');
        $driver = $connection->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) {
            $this->components->error("Unsupported central database driver [{$driver}] for schema cleanup.");

            return self::FAILURE;
        }

        $keep = ['migrations', 'users', 'plans', 'tenants', 'tenant_subscriptions', 'support_threads', 'support_messages'];
        $tables = $this->listTables($driver);
        $drop = array_values(array_diff($tables, $keep));

        Schema::connection('central')->disableForeignKeyConstraints();

        foreach ($drop as $table) {
            Schema::connection('central')->dropIfExists($table);
        }

        Schema::connection('central')->enableForeignKeyConstraints();

        $this->components->info('Central schema cleaned successfully.');
        $this->line('Kept tables: '.implode(', ', $keep));

        if ($drop !== []) {
            $this->line('Dropped tables: '.implode(', ', $drop));
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function listTables(string $driver): array
    {
        $connection = DB::connection('central');

        if ($driver === 'sqlite') {
            return collect($connection->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"))
                ->map(fn ($row) => (string) $row->name)
                ->values()
                ->all();
        }

        $database = (string) config('database.connections.central.database');

        return collect($connection->select('SHOW TABLES'))
            ->map(function ($row) use ($database) {
                $key = 'Tables_in_'.$database;

                return isset($row->$key) ? (string) $row->$key : (string) array_values((array) $row)[0];
            })
            ->values()
            ->all();
    }
}
