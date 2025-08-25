<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class LscVersions extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('lsc_versions');
        $table
            ->addColumn('version', 'string', ['limit' => 50])
            ->addColumn('created_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP'
            ])
            ->create();
    }
}
