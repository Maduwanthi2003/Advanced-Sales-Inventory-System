<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected const EXPECTED_TEST_DB = 'assignment02_crud_testing';
    protected const FORBIDDEN_DEV_DB = 'assignment02_crud';

    /**
     * Refresh the application instance and immediately lock database configuration
     * BEFORE any test traits (such as DatabaseTransactions or RefreshDatabase) run.
     */
    protected function refreshApplication(): void
    {
        putenv('APP_ENV=testing');
        putenv('DB_CONNECTION=mysql');
        putenv('DB_DATABASE=' . self::EXPECTED_TEST_DB);
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['DB_CONNECTION'] = 'mysql';
        $_ENV['DB_DATABASE'] = self::EXPECTED_TEST_DB;
        $_SERVER['APP_ENV'] = 'testing';
        $_SERVER['DB_CONNECTION'] = 'mysql';
        $_SERVER['DB_DATABASE'] = self::EXPECTED_TEST_DB;

        parent::refreshApplication();

        $this->enforceIsolatedTestingDatabase();
    }

    /**
     * Override setUpTraits so the fail-closed database check runs BEFORE
     * any database-mutating trait initialization.
     */
    protected function setUpTraits(): array
    {
        $this->enforceIsolatedTestingDatabase();

        return parent::setUpTraits();
    }

    /**
     * Bind a valid single-session token and SHA-256 hash when authenticating
     * test users via actingAs(), without purging the active database transaction.
     */
    public function actingAs(UserContract $user, $guard = null)
    {
        $this->assertIsolatedTestingDatabaseWithoutPurge();

        if ($user instanceof User && $user->exists) {
            $sessionToken = Str::random(64);
            $user->forceFill([
                'active_session_hash' => hash('sha256', $sessionToken),
            ])->saveQuietly();

            $this->withSession([
                'auth_session_token' => $sessionToken,
            ]);
        }

        return parent::actingAs($user, $guard);
    }

    /**
     * Verify active connection is assignment02_crud_testing without calling DB::purge()
     * so in-flight DatabaseTransactions are preserved.
     */
    protected function assertIsolatedTestingDatabaseWithoutPurge(): void
    {
        $driver = DB::connection()->getDriverName();
        $configuredDb = DB::connection()->getDatabaseName();

        if ($driver !== 'mysql' || $configuredDb !== self::EXPECTED_TEST_DB || $configuredDb === self::FORBIDDEN_DEV_DB) {
            throw new RuntimeException(sprintf(
                "CRITICAL SAFETY ABORT: Invalid test database connection (driver=%s, configured=%s). Expected mysql:%s.",
                (string) $driver,
                (string) $configuredDb,
                self::EXPECTED_TEST_DB
            ));
        }
    }

    /**
     * Fail-closed verification that the active connection is strictly
     * mysql -> assignment02_crud_testing and never assignment02_crud.
     */
    protected function enforceIsolatedTestingDatabase(): void
    {
        if (!$this->app) {
            throw new RuntimeException('Application instance not initialized.');
        }

        // Force config to use only assignment02_crud_testing on mysql
        $this->app['config']->set('database.default', 'mysql');
        $this->app['config']->set('database.connections.mysql.database', self::EXPECTED_TEST_DB);

        // Ensure no configured connection points to the development database
        $connections = (array) $this->app['config']->get('database.connections', []);
        foreach ($connections as $name => $connConfig) {
            if (($connConfig['database'] ?? null) === self::FORBIDDEN_DEV_DB) {
                $this->app['config']->set("database.connections.{$name}.database", self::EXPECTED_TEST_DB);
            }
        }

        DB::purge('mysql');

        $driver = DB::connection()->getDriverName();
        $configuredDb = DB::connection()->getDatabaseName();
        $serverRow = DB::connection()->selectOne('SELECT DATABASE() AS db');
        $actualServerDb = $serverRow->db ?? null;

        if (
            $driver !== 'mysql'
            || $configuredDb !== self::EXPECTED_TEST_DB
            || $actualServerDb !== self::EXPECTED_TEST_DB
            || $configuredDb === self::FORBIDDEN_DEV_DB
            || $actualServerDb === self::FORBIDDEN_DEV_DB
        ) {
            throw new RuntimeException(sprintf(
                "CRITICAL SAFETY ABORT: Invalid test database connection (driver=%s, configured=%s, server=%s). Expected mysql:%s.",
                (string) $driver,
                (string) $configuredDb,
                (string) $actualServerDb,
                self::EXPECTED_TEST_DB
            ));
        }
    }
}
