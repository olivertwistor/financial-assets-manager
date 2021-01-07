<?php
declare(strict_types=1);

namespace Olivertwistor\AssetManager;

use Exception;

/**
 * This exception occurs if something goes wrong with the {@link Database}
 * class.
 *
 * @since 0.1.0
 */
class DatabaseException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
