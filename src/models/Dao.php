<?php
declare(strict_types=1);

namespace Olivertwistor\AssetManager\models;

use Olivertwistor\AssetManager\db\Database;
use Olivertwistor\AssetManager\db\DatabaseException;

/**
 * A class that implements this inferface provides CRUD operations. It can
 * create a record in a supplied database, it can read one or more records from
 * a supplied database, it can update a record in a supplied database, and it
 * can delete a record in a supplied database.
 *
 * @since 0.1.0
 */
interface Dao
{
    /**
     * Creates a record corresponding to this object in the supplied database.
     * Also, retrieves the row ID of the newly created record and stores it in
     * this object.
     *
     * @param Database $database the database in which to create the record
     *
     * @throws DatabaseException if something goes wrong with the database
     *
     * @since 0.1.0
     */
    public function create(Database $database) : void;

    /**
     * Reads a record from the supplied database.
     *
     * @param int $id            unique identifier for which record to read
     * @param Database $database the database in which to create the record
     *
     * @return Dao|null An object corresponding to the database record that was
     *                  read, or null if no record could be found.
     *
     * @throws DatabaseException if something goes wrong with the database
     *
     * @since 0.1.0
     */
    public static function read(int $id, Database $database) : ?Dao;

    /**
     * Reads all records of this type from the supplied database.
     *
     * @param Database $database the database in which to create the record
     *
     * @return Dao[] An array of objects corresponding to the database records
     *               that were read.
     *
     * @throws DatabaseException if something goes wrong with the database
     *
     * @since 0.1.0
     */
    public static function readAll(Database $database) : array;

    /**
     * Updates the record corresponding to this object in the supplied database.
     *
     * @param Database $database the database in which to update the record
     *
     * @throws DatabaseException if something goes wrong with the database
     *
     * @since 0.1.0
     */
    public function update(Database $database) : void;

    /**
     * Deletes the record corresponding to this object from the supplied
     * database.
     *
     * @param Database $database the database from which to delete the record
     *
     * @throws DatabaseException if something goes wrong with the database
     *
     * @since 0.1.0
     */
    public function delete(Database $database) : void;
}
