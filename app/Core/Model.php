<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Base de los modelos: UNICA capa que habla con MySQL.
 */
abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = \Database::conectar();
    }
}
