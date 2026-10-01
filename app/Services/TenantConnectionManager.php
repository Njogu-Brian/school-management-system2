<?php

namespace App\Services;

use App\Models\SchoolRegistry;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TenantConnectionManager
{
    public function configureFromSchool(SchoolRegistry $school): void
    {
        if (! filled($school->db_name)) {
            throw new RuntimeException("School {$school->code} has no database configured.");
        }

        $password = $school->db_password;
        Config::set('database.connections.tenant', [
            'driver' => 'mysql',
            'host' => $school->db_host ?: '127.0.0.1',
            'port' => $school->db_port ?: 3306,
            'database' => $school->db_name,
            'username' => $school->db_username ?: $school->db_name,
            'password' => $password ?? '',
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ]);

        DB::purge('tenant');
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');
    }

    /**
     * @param  array{db_name:string,db_username:string,db_password:string,db_host?:string,db_port?:int}  $creds
     */
    public function configureTemporary(array $creds, string $as = 'tenant'): void
    {
        Config::set("database.connections.{$as}", [
            'driver' => 'mysql',
            'host' => $creds['db_host'] ?? '127.0.0.1',
            'port' => $creds['db_port'] ?? 3306,
            'database' => $creds['db_name'],
            'username' => $creds['db_username'],
            'password' => $creds['db_password'],
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ]);
        DB::purge($as);
        DB::reconnect($as);
    }

    public function restoreDefault(): void
    {
        DB::setDefaultConnection(config('database.default', 'mysql'));
    }
}
