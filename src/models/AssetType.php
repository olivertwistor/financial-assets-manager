<?php
declare(strict_types=1);

namespace Olivertwistor\AssetManager\models;

use Olivertwistor\AssetManager\db\Database;

/**
 * This class defines an asset type, for example stocks, bonds, real estate or
 * currency.
 *
 * @since 0.1.0
 */
class AssetType implements Dao
{
    /**
     * Row ID in the database in which this object is stored.
     *
     * @var int
     *
     * @since 0.1.0
     */
    private $id;

    /**
     * Name of this asset type.
     *
     * @var string
     *
     * @since 0.1.0
     */
    private $name;

    public function __construct(string $name, int $id=0)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function create(Database $database): void
    {
        $create_sql = <<<SQL
INSERT INTO asset_type (name)
VALUES (':name');
SQL;
        $database->execute($create_sql, ['name' => $this->name]);
        $this->id = $database->getLastInsertedRowId();

    }

    public static function read(int $id, Database $database): ?Dao
    {
        $read_sql = <<<SQL
SELECT name
FROM asset_type
WHERE id = $id;
SQL;
        $name = $database->querySingle($read_sql);

        if ($name != null)
        {
            return new AssetType($name, $id);
        }
        return null;
    }

    public static function readAll(Database $database): array
    {
        $read_sql = <<<SQL
SELECT id, name
FROM asset_type;
SQL;
        $results = $database->execute($read_sql);

        // Loop through the result set.
        $asset_types = [];
        while (true)
        {
            $row = $results->fetchArray();

            // We must first check for the empty result set. SQLite3 returns a
            // row, but with the content of the first column == null. If we see
            // that, we must break out of the loop.
            if ($row[0] == null)
            {
                break;
            }

            $id = $row[0];
            $name = $row[1];

            $asset_type = new AssetType($name, $id);
            $asset_types[] = $asset_type;
        }

        return $asset_types;
    }

    public function update(Database $database): void
    {
        $update_sql = <<<SQL
UPDATE asset_type
SET name = :name
WHERE id = :id;
SQL;
        $database->execute(
            $update_sql, [':id' => $this->id, ':name' => $this->name]);
    }

    public function delete(Database $database): void
    {
        $delete_sql = <<<SQL
DELETE FROM asset_type
WHERE id = :id;
SQL;
        $database->execute($delete_sql, ['id' => $this->id]);
    }

    public function __toString(): string
    {
        return "AssetType{id={$this->id}, name={$this->name}}";
    }
}
