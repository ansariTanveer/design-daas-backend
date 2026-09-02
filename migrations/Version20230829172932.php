<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230829172932 extends AbstractMigration
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
            'CREATE TABLE users_to_user_groups (
                user_id BIGINT NOT NULL,
                user_group_id BIGINT NOT NULL,
                INDEX IDX_C514859BA76ED395 (user_id),
                INDEX IDX_C514859B1ED93D47 (user_group_id),
                PRIMARY KEY(user_id, user_group_id)
            )
            DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );
        $this->addSql(
            /** @lang MySQL */
            'ALTER TABLE users_to_user_groups
                ADD CONSTRAINT FK_C514859BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)'
        );
        $this->addSql(
            /** @lang MySQL */
            'ALTER TABLE users_to_user_groups
                ADD CONSTRAINT FK_C514859B1ED93D47 FOREIGN KEY (user_group_id) REFERENCES user_groups (id)'
        );
        $this->addSql(/** @lang MySQL */'DROP TABLE baseuser_usergroup');
        $this->addSql(/** @lang MySQL */'ALTER TABLE users CHANGE enabled enabled TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(
            /** @lang MySQL */
            'CREATE TABLE baseuser_usergroup (
                baseuser_id BIGINT NOT NULL,
                usergroup_id BIGINT NOT NULL,
                INDEX IDX_6D6F8064D2112630 (usergroup_id),
                INDEX IDX_6D6F8064363DAC7F (baseuser_id),
                PRIMARY KEY(baseuser_id, usergroup_id)
            )
            DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\''
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
        $this->addSql(/** @lang MySQL */'DROP TABLE users_to_user_groups');
        $this->addSql(
            /** @lang MySQL */
            'ALTER TABLE users
                CHANGE enabled enabled TINYINT(1) DEFAULT \'1\' NOT NULL'
        );
    }
}
