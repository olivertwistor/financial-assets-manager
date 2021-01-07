<?php
declare(strict_types=1);

namespace Olivertwistor\AssetManager;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the {@link Database} class.
 *
 * @since 0.1.0
 */
final class DatabaseTest extends TestCase
{
    /**
     * Database to use for testing.
     *
     * @var Database
     *
     * @since 0.1.0
     */
    private static $database;

    public static function setUpBeforeClass() : void
    {
        self::$database = new Database('tests/test.sqlite3');
    }

    /** @test */
    public function getDbVersionGivesNumberGreaterThanOrEqualToZero() : void
    {
        try
        {
            $version = self::$database->getDbVersion();

            self::assertGreaterThanOrEqual(0, $version);
        }
        catch (DatabaseException $e)
        {
            self::fail($e->getMessage());
        }
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function setDbVersionWithDefaultDateDoesNotThrowException() : void
    {
        $random_version = rand(1, 1000000);

        try
        {
            self::$database->setDbVersion($random_version);
        }
        catch (DatabaseException $e)
        {
            self::fail($e->getMessage());
        }
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function setDbVersionWithDateDoesNotThrowException() : void
    {
        $random_version = rand(1, 1000000);

        try
        {
            self::$database->setDbVersion($random_version, '1997-08-20');
        }
        catch (DatabaseException $e)
        {
            self::fail($e->getMessage());
        }
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function beginAndCommitTransactionDoesNotThrowException() : void
    {
        try
        {
            self::$database->beginTransaction();
            self::$database->commitTransaction();
        }
        catch (DatabaseException $e)
        {
            self::fail($e->getMessage());
        }
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function beginAndRollbackTransactionDoesNotThrowException() : void
    {
        try
        {
            self::$database->beginTransaction();
            self::$database->rollbackTransaction();
        }
        catch (DatabaseException $e)
        {
            self::fail($e->getMessage());
        }
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function executeStatementWithoutParamsDoesNotThrowException() : void
    {
        $random_version = rand(1, 1000000);
        $todays_date = date('Y-m-d');

        $insert_db_version_sql = <<<SQL
INSERT INTO db_version (version, date)
VALUES ($random_version, $todays_date);
SQL;

        try
        {
            self::$database->executeStatement($insert_db_version_sql);
        }
        catch (DatabaseException $e)
        {
            self::fail($e->getMessage());
        }
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function executeStatementWithParamsDoesNotThrowException() : void
    {
        $random_version = rand(1, 1000000);
        $todays_date = date('Y-m-d');

        try
        {
            self::$database->executeStatement('
                INSERT INTO db_version (version, date) 
                VALUES (:version, :date)',
                [
                    ':version' => $random_version,
                    ':date' => $todays_date
                ]
            );
        }
        catch (DatabaseException $e)
        {
            self::fail($e->getMessage());
        }
    }

    public static function tearDownAfterClass() : void
    {
        self::$database = null;
    }
}
