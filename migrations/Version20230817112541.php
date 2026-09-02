<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230817112541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            /** @lang MySQL */
            'CREATE TABLE oauth2_access_token (
                identifier VARCHAR(200) NOT NULL,
                create_moment DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                valid_until_moment DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                client_identifier VARCHAR(200) NOT NULL,
                scopes LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\',
                user_identifier VARCHAR(200) DEFAULT NULL,
                revoke_moment DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                PRIMARY KEY(identifier)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );
        $this->addSql(
            /** @lang MySQL */
            'CREATE TABLE oauth2_clients (
                identifier VARCHAR(200) NOT NULL,
                version INT DEFAULT 1 NOT NULL,
                name VARCHAR(200) NOT NULL,
                redirect_uris LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\',
                is_confidential TINYINT(1) NOT NULL,
                secret VARCHAR(200) NOT NULL, PRIMARY KEY(identifier)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );
        $this->addSql(
            /** @lang MySQL */
            'CREATE TABLE oauth2_refresh_tokens (
                    identifier VARCHAR(200) NOT NULL,
                    create_moment DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                    valid_until_moment DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                    access_token VARCHAR(200) NOT NULL,
                    client_identifier VARCHAR(200) NOT NULL,
                    scopes LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\',
                    user_identifier VARCHAR(200) DEFAULT NULL,
                    revoke_moment DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                    PRIMARY KEY(identifier)
               ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */'DROP TABLE oauth2_access_token');
        $this->addSql(/** @lang MySQL */'DROP TABLE oauth2_clients');
        $this->addSql(/** @lang MySQL */'DROP TABLE oauth2_refresh_tokens');
    }
}
