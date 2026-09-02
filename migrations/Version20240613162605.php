<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240613162605 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set default user enabled value to false';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            /** @lang MySQL */
            'ALTER TABLE users
                    CHANGE enabled enabled TINYINT(1) NOT NULL DEFAULT 0',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            /** @lang MySQL */
            'ALTER TABLE users
                    CHANGE enabled enabled TINYINT(1) NOT NULL DEFAULT 1',
        );
    }
}
