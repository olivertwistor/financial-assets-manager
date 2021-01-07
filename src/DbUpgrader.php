<?php
declare(strict_types=1);

namespace Olivertwistor\AssetManager;

/**
 * The DbUgrader handles upgrading the database schema to the current version.
 * When a new version of the application is released, the database schema may
 * have been changed. Thus, a database upgrade must be performed on older
 * databases to match the new schema without losing previously stored data.
 *
 * Please note that the application and database version numbers don't match.
 * Application versions are named MAJOR.MINOR.FIX, while database versions are
 * named with incremented integers starting at 1.
 *
 * @since 0.1.0
 */
class DbUpgrader
{
    /**
     * Upgrades a database to the latest version. Updates the db_version table
     * to reflect that fact.
     *
     * This method updates the database one version at a time until it reaches
     * the latest version. Each upgrade step is wrapped inside of a database
     * transaction, so if any step fails, the transaction is rolled back, and
     * this method returns immediately to prevent further upgrades.
     *
     * @param Database $database the database to use
     *
     * @return bool Whether the upgrade succeeded.
     *
     * @throws DatabaseException if the database transaction handling fails
     *
     * @since 0.1.0
     */
    public function upgrade(Database $database) : bool
    {
        $old_version = $database->getDbVersion();

        // Upgrade from version 0 to 1.
        if ($old_version < 1)
        {
            $database->beginTransaction();

            try
            {
                // Create the db_version table holding database version
                // information.
                $create_db_version_table_sql = <<<SQL
CREATE TABLE IF NOT EXISTS db_version
(
    version INTEGER NOT NULL,
    date    TEXT    NOT NULL
);
SQL;
                $database->executeStatement($create_db_version_table_sql);

                // Add an index on the date column of the database version
                // table, since we're sorting on date quite often.
                $create_db_version_index_sql = <<<SQL
CREATE INDEX IF NOT EXISTS idx_date
ON db_version(date);
SQL;
                $database->executeStatement($create_db_version_index_sql);

                // We're done with upgrading to version 1. Update the database
                // to reflect that.
                $database->setDbVersion(1);
            }
            catch (DatabaseException $e)
            {
                try
                {
                    $database->rollbackTransaction();
                }
                catch (DatabaseException $e1)
                {
                    error_log("Transaction rollback failed: $e");
                }
                finally
                {
                    return false;
                }
            }
        }

        return true;
    }
}
