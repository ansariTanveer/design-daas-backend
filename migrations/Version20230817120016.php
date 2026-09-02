<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230817120016 extends AbstractMigration
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
            'CREATE TABLE users (
                id BIGINT AUTO_INCREMENT NOT NULL,
                guid VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                enabled TINYINT(1) NOT NULL DEFAULT 1,
                role VARCHAR(16) NOT NULL,
                INDEX guid_idx (guid),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );
        $this->addSql(
            /** @lang MySQL */
            'CREATE TABLE baseuser_usergroup (
                baseuser_id BIGINT NOT NULL,
                usergroup_id BIGINT NOT NULL,
                INDEX IDX_6D6F8064363DAC7F (baseuser_id),
                INDEX IDX_6D6F8064D2112630 (usergroup_id),
                PRIMARY KEY(baseuser_id, usergroup_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );
        $this->addSql(
            /** @lang MySQL */
            'ALTER TABLE baseuser_usergroup
                ADD CONSTRAINT FK_6D6F8064363DAC7F FOREIGN KEY (baseuser_id)
                REFERENCES users (id) ON DELETE CASCADE'
        );
        $this->addSql(
            /** @lang MySQL */
            'ALTER TABLE baseuser_usergroup
                ADD CONSTRAINT FK_6D6F8064D2112630 FOREIGN KEY (usergroup_id)
                REFERENCES user_groups (id) ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */ 'ALTER TABLE baseuser_usergroup DROP FOREIGN KEY FK_6D6F8064363DAC7F');
        $this->addSql(/** @lang MySQL */ 'DROP TABLE users');
        $this->addSql(/** @lang MySQL */ 'DROP TABLE baseuser_usergroup');
    }
}
