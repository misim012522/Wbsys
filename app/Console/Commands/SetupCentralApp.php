<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use PDO;

class SetupCentralApp extends Command
{
    protected $signature = 'central:setup
                            {username : Username for the central sysadmin account}
                            {password : Password for the central sysadmin account}
                            {--name=System Administrator : Display name for the sysadmin account}
                            {--email=sysadmin@example.com : Email address for the sysadmin account}
                            {--fresh : Drop all tables in the central database before migrating}';

    protected $description = 'Create the central database, run central migrations, and seed the sysadmin account.';

    public function handle(): int
    {
        try {
            $this->prepareCentralDatabase();
        } catch (\Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->callSilent('central:migrate', [
            '--fresh' => $this->option('fresh'),
        ]);

        $this->callSilent('db:seed', [
            '--class' => 'Database\\Seeders\\DatabaseSeeder',
            '--force' => true,
        ]);

        $this->upsertCentralAdmin();

        $this->components->info('Central app database is ready.');
        $this->line('Username: '.$this->argument('username'));
        $this->line('Database: '.config('database.connections.central.database'));

        return self::SUCCESS;
    }

    private function prepareCentralDatabase(): void
    {
        $config = config('database.connections.central');
        $driver = $config['driver'] ?? 'mysql';
        $centralDatabase = (string) ($config['database'] ?? '');
        $defaultConfig = config('database.connections.'.config('database.default'))
            ?? config('database.connections.mysql')
            ?? [];
        $defaultDatabase = (string) ($defaultConfig['database'] ?? '');

        if ($centralDatabase === '') {
            throw new \RuntimeException('CENTRAL_DB_DATABASE must be configured before running central:setup.');
        }

        if ($centralDatabase === $defaultDatabase) {
            throw new \RuntimeException(
                'The central database must be separate from the main/tenant database. Set CENTRAL_DB_DATABASE to a different database name, then run central:setup again.'
            );
        }

        if ($driver === 'sqlite') {
            $database = $centralDatabase ?: database_path('central.sqlite');
            File::ensureDirectoryExists(dirname($database));

            if (! File::exists($database)) {
                File::put($database, '');
            }

            return;
        }

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new \RuntimeException("Unsupported central database driver [{$driver}] for automatic setup.");
        }

        $dsn = sprintf(
            '%s:host=%s;port=%s;charset=%s',
            $driver === 'mariadb' ? 'mysql' : $driver,
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? '3306',
            $config['charset'] ?? 'utf8mb4',
        );

        $pdo = new PDO(
            $dsn,
            $config['username'] ?? '',
            $config['password'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $pdo->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s',
            str_replace('`', '``', $centralDatabase),
            $config['charset'] ?? 'utf8mb4',
            $config['collation'] ?? 'utf8mb4_unicode_ci',
        ));

        DB::purge('central');
    }

    private function upsertCentralAdmin(): void
    {
        $now = now();

        DB::connection('central')->table('users')->updateOrInsert(
            ['username' => (string) $this->argument('username')],
            [
                'name' => (string) $this->option('name'),
                'email' => (string) $this->option('email'),
                'password' => Hash::make((string) $this->argument('password')),
                'role' => User::ROLE_SYSTEM_ADMIN,
                'tenant_id' => null,
                'approved_at' => $now,
                'email_verified_at' => $now,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }
}
