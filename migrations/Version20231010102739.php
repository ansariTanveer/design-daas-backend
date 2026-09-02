<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231010102739 extends AbstractMigration
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
            'CREATE TABLE desktopgroup_usergroup (
                desktopgroup_id BIGINT NOT NULL, usergroup_id BIGINT NOT NULL,
                INDEX IDX_C86D8974CD3A118 (desktopgroup_id),
                INDEX IDX_C86D897D2112630 (usergroup_id),
                PRIMARY KEY(desktopgroup_id, usergroup_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );

        $this->addSql(
            /** @lang MySQL */
            'ALTER TABLE desktopgroup_usergroup
                ADD CONSTRAINT FK_C86D8974CD3A118 FOREIGN KEY (desktopgroup_id)
                    REFERENCES desktop_groups (id) ON DELETE CASCADE'
        );

        $this->addSql(
            /** @lang MySQL */
            'ALTER TABLE desktopgroup_usergroup ADD CONSTRAINT FK_C86D897D2112630 FOREIGN KEY (usergroup_id)
                    REFERENCES user_groups (id) ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */ 'DROP TABLE desktopgroup_usergroup');
    }
}
