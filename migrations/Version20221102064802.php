<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Enqueue\Dbal\DbalContext;

final class Version20221102064802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add table for internal message broker';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('# \Enqueue\Dbal\DbalContext::createDataBaseTable()'); // prevent Doctrine warning

        $context = new DbalContext($this->connection, ['table_name' => 'enqueue']);
        $context->createDataBaseTable();
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('enqueue');
    }
}
