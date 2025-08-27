<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ConfigTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('config', [
            'id' => 'id',           // auto-increment primary key
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        $table
            ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('value', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false
            ])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update'  => 'CURRENT_TIMESTAMP',
                'null' => false
            ])
            ->addIndex(['name'], ['unique' => true]) // unique name for ON DUPLICATE KEY UPDATE
            ->create();
    }
}
