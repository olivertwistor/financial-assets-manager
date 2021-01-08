<?php
declare(strict_types=1);

namespace Olivertwistor\AssetManager\db;

use InvalidArgumentException;
use SQLite3;
use SQLite3Result;

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
        $this->dataSource = new SQLite3(
            $filename, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
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
        $table_name_sql = <<<SQL
SELECT name 
FROM sqlite_master 
WHERE type='table' AND name='db_version';
SQL;
        $table_name = $this->querySingle($table_name_sql);
        if ($table_name == null)
        {
            return 0;
        }

        // We now know there is a db_version table. By looking at the row with
        // the latest date, we can determine the current version of the
        // database.
        $current_version_sql = <<<SQL
SELECT version
FROM db_version 
ORDER BY date, version DESC 
LIMIT 1;
SQL;
        $current_version = $this->querySingle($current_version_sql);
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
        // Validate arguments. Version must not be lower than 1. If Date is
        // null, today's date is used.
        if ($version < 1)
        {
            throw new InvalidArgumentException(
                'Version must not be lower than 1.');
        }
        if ($date == null)
        {
            $date = date('Y-m-d');
        }

        // Insert the version and the date into the database.
        $insert_db_version_sql = <<<SQL
INSERT INTO db_version (version, date)
VALUES ($version, $date);
SQL;
        $this->execute($insert_db_version_sql);
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
     * Executes an SQL query or statement and returns the result set.
     *
     * @param string $sql   the SQL statement to execute
     * @param array $params associative array with parameters to be bound if
     *                      the SQL statement has placeholders; default is an
     *                      empty array (meaning no parameters will be bound)
     *
     * @return SQLite3Result|null The result set from the query, or null if the
     *                            SQL is a pure statement (e.g. INSERT).
     *
     * @throws DatabaseException if something goes wrong with the preparation
     *                           of the SQL, binding of parameters or execution
     *                           of the query or statement.
     *
     * @since 0.1.0
     */
    public function execute(string $sql, array $params = []) : ?SQLite3Result
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
            $success = $statement->bindValue($key, $value);
            if (!$success)
            {
                $statement->close();
                throw new DatabaseException(
                    "Failed to bind parameter pair $key => $value to " .
                    "statement $sql.");
            }
        }

        // Now we can finally execute the statement.
        $result = $statement->execute();
        if (!$result)
        {
            $statement->close();
            throw new DatabaseException('Failed to execute statement.');
        }

        // Return the result set if it's a query; null otherwise.
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        if ($statement->readOnly())
        {
            return null;
        }
        return $result;
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
    public function querySingle(string $sql, bool $entireRow=false)
    {
        $result = $this->dataSource->querySingle($sql, $entireRow);

        if (($result != null) && ($result == false))
        {
            throw new DatabaseException("Invalid query: $result");
        }

        return $result;
    }

    /**
     * Retrieves the row ID of the last INSERT statement to this database.
     *
     * @return int
     *
     * @since 0.1.0
     */
    public function getLastInsertedRowId(): int
    {
        return $this->dataSource->lastInsertRowID();
    }

    public function __toString() : string
    {
        return "Database{dataSource={$this->dataSource}}";
    }
}
