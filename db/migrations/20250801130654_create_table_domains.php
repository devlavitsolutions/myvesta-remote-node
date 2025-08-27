<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTableDomains extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     */
    public function change(): void
    {
        // Include the custom Database class
        require_once __DIR__ . '/../Database.php';

        // Initialize the Database class with the config file
        $db = new Database(__DIR__ . '/../../conf/.mysql.localhost');

        // Connect to the database
        $db->connect();

        // Check if the 'domains' table exists using the Database class
        if (!$db->hasTable('domains')) {
            // Table does not exist, create it
            $table = $this->table('domains');
            $table->addColumn('name', 'string', ['limit' => 255, 'null' => false])
                  ->addColumn('hasWordPress', 'boolean', ['default' => false, 'null' => false])
                  ->addColumn('isCacheEnabled', 'boolean', ['default' => false, 'null' => false])
                  ->addColumn('isFlagged', 'boolean', ['default' => false, 'null' => false])
                  ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                  ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                  ->create();
        }
    }
}