<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240610152732 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user registration_used_moment field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ 'ALTER TABLE users
            ADD registration_used_moment DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ 'ALTER TABLE users DROP registration_used_moment');
    }
}
