<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240614081756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Longer registration_code, add index';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
        /** @lang MySQL */
            'ALTER TABLE users CHANGE registration_code registration_code VARCHAR(8) DEFAULT NULL',
        );
        // The registration code is set in constructor from now on, so we make sure the column is populated
        $this->addSql(
        /** @lang MySQL */
            'UPDATE users SET registration_code = SUBSTRING(UPPER(HEX(UUID_SHORT())),0,8)
             WHERE users.role = "user" AND registration_code IS NULL OR registration_code = ""',
        );
        $this->addSql('CREATE INDEX registration_idx ON users (email, registration_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX registration_idx ON users');
        $this->addSql(
        /** @lang MySQL */
            'ALTER TABLE users CHANGE registration_code registration_code VARCHAR(5) DEFAULT NULL',
        );
    }
}
