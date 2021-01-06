<?php
declare(strict_types=1);

namespace Olivertwistor\AssetManager;

use InvalidArgumentException;
use SQLite3;

/**
 * This class is a wrapper for SQLite3 database methods. It also provides
 * methods for handling transactions as well as things specific to this
 * application, for example getting the current database version.
 *
 * @since 0.1.0
 */
class Database
{
    /**
     * The SQLite3 data source.
     *
     * @var SQLite3
     *
     * @since 0.1.0
     */
    private $dataSource;

    public function __construct(string $filename)
    {
        $this->dataSource = new SQLite3($filename, SQLITE3_OPEN_CREATE);
    }

    /**
     * Gets the current version of the database.
     *
     * The current version is defined as the version with the latest date. If
     * multiple version has the same date, the highest version number is
     * returned.
     *
     * @return int The database version number.
     *
     * @throws DatabaseException if communications with the database failed
     *
     * @since 0.1.0
     */
    public function getDbVersion() : int
    {
        // First, we need to determine whether the db_version table exists at
        // all. If it doesn't, we're dealing with a brand new database, and
        // should return 0.
        $table_name = $this->querySingle("
            SELECT name 
            FROM sqlite_master 
            WHERE type='table' AND name='db_version'
        ");
        if ($table_name == null)
        {
            return 0;
        }

        // We now know there is a db_version table. By looking at the row with
        // the latest date, we can determine the current version of the
        // database.
        $current_version = $this->querySingle('
            SELECT version 
            FROM db_version 
            ORDER BY date, version DESC 
            LIMIT 1'
        );
        if ($current_version == null)
        {
            return 0;
        }

        return $current_version;
    }

    /**
     * Sets the database version.
     *
     * @param int $version      the version number to set
     * @param string|null $date the date for the database version; default is
     *                          null, which means that today's date will be used
     *
     * @throws DatabaseException if inserting into the database failed
     *
     * @since 0.1.0
     */
    public function setDbVersion(int $version, string $date = null) : void
    {
        // Validate arguments. Version must not be lower than 1 and Date must
        // have a valid format (YYYY-mm-dd). If Date is null, today's date is
        // used.
        if ($version < 1)
        {
            throw new InvalidArgumentException(
                'Version must not be lower than 1.');
        }
        if ($date == null)
        {
            $date = date('YYYY-mm-dd');
        }
        elseif (preg_match('%d{4}-%d{2}-%d{2}', $date) != 1)
        {
            throw new InvalidArgumentException(
                'Date must have the format YYYY-mm-dd.');
        }

        // Insert the version and the date into the database.
        $this->executeStatement("
            INSERT INTO db_version (version, date) 
            VALUES ($version, $date)
        ");
    }

    /**
     * Begins a database transaction.
     *
     * @throws DatabaseException if a transaction failed to start
     *
     * @since 0.1.0
     */
    public function beginTransaction() : void
    {
        $success = $this->dataSource->exec('BEGIN;');
        if (!$success)
        {
            throw new DatabaseException('Failed to begin transaction.');
        }
    }

    /**
     * Commits an open database transaction.
     *
     * @throws DatabaseException if the transaction failed to be committed.
     *
     * @since 0.1.0
     */
    public function commitTransaction() : void
    {
        $success = $this->dataSource->exec('COMMIT;');
        if (!$success)
        {
            throw new DatabaseException('Failed to commit transaction.');
        }
    }

    /**
     * Roll backs an open transaction.
     *
     * @throws DatabaseException if the transaction failed to be rolled back.
     *
     * @since 0.1.0
     */
    public function rollbackTransaction() : void
    {
        $success = $this->dataSource->exec('ROLLBACK;');
        if (!$success)
        {
            throw new DatabaseException('Failed to rollback transaction.');
        }
    }

    /**
     * Executes an SQL statement. This method is not intended for queries where
     * a result is expected, but for INSERTs, UPDATEs etc. Thus, this method
     * doesn't return anything.
     *
     * @param string $sql   the SQL statement to execute
     * @param array $params associative array with parameters to be bound if
     *                      the SQL statement has named placeholders; default
     *                      is an empty array (meaning no parameters will be
     *                      bound)
     *
     * @throws DatabaseException if something goes wrong with the preparation
     *                           of the SQL, binding of parameters or execution
     *                           of the statement.
     *
     * @since 0.1.0
     */
    public function executeStatement(string $sql, array $params = []) : void
    {
        // Try to prepare the SQL.
        $statement = $this->dataSource->prepare($sql);
        if (!$statement)
        {
            throw new DatabaseException(
                "Failed to prepare statement $sql with parameters $params.");
        }

        // If we have parameters, we'll bind them to the statement.
        foreach ($params as $key => $value)
        {
            $success = $statement->bindParam($key, $value);
            if (!$success)
            {
                throw new DatabaseException(
                    "Failed to bind parameter pair $key => $value to " .
                    "statement $sql.");
            }
        }

        // Now we can finally execute the statement.
        $result = $statement->execute();
        if (!$result)
        {
            throw new DatabaseException('Failed to execute statement.');
        }

    }

    /**
     * Executes an SQL query and gets the first record. If $entireRow is set to
     * false (default), only the first column is returned. Otherwise the whole
     * record is returned as an array.
     *
     * @param string $sql     the SQL query to execute
     * @param bool $entireRow whether the entire row shall be returned (true)
     *                        or only the first column (false); default is false
     *
     * @return mixed|null The query result, or null if the result is empty
     *
     * @throws DatabaseException if the SQL query is invalid.
     *
     * @since 0.1.0
     */
    private function querySingle(string $sql, bool $entireRow=false)
    {
        $result = $this->dataSource->querySingle($sql, $entireRow);

        if (($result != null) && ($result == false))
        {
            throw new DatabaseException("Invalid query: $result");
        }

        return $result;
    }

    public function __toString() : string
    {
        return "Database{dataSource={$this->dataSource}}";
    }
}
