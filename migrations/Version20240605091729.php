<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240605091729 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */
            'ALTER TABLE users
                ADD registration_code VARCHAR(5) DEFAULT NULL,
                ADD registration_code_timeout DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\''
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */
            'ALTER TABLE users
                DROP registration_code,
                DROP registration_code_timeout'
        );
    }
}
