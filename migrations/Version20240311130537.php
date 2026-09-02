<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240311130537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */
            'CREATE TABLE endpoint (
                id INT NOT NULL,
                endpoint_group_name VARCHAR(255) DEFAULT NULL,
                function_name VARCHAR(255) NOT NULL,
                endpoint_url VARCHAR(255) NOT NULL,
                method VARCHAR(16) NOT NULL,
                INDEX IDX_C4420F7B36B23978 (endpoint_group_name),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );
        $this->addSql(/** @lang MySQL */
            'CREATE TABLE endpoint_group (
                unique_group_name VARCHAR(255) NOT NULL,
                PRIMARY KEY(unique_group_name)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );
        $this->addSql(/** @lang MySQL */
            'CREATE TABLE endpoint_group_user_access (
                id INT AUTO_INCREMENT NOT NULL,
                endpoint_group_name VARCHAR(255) DEFAULT NULL,
                user_id BIGINT NOT NULL,
                relation VARCHAR(255) NOT NULL,
                INDEX IDX_DFC5708736B23978 (endpoint_group_name),
                INDEX IDX_DFC57087A76ED395 (user_id),
                UNIQUE INDEX lookup_unique_idx (endpoint_group_name, user_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );
        $this->addSql(/** @lang MySQL */
            'CREATE TABLE endpoint_group_user_group_access (
                id INT AUTO_INCREMENT NOT NULL,
                endpoint_group_name VARCHAR(255) DEFAULT NULL,
                user_group_id BIGINT NOT NULL,
                relation VARCHAR(255) NOT NULL,
                INDEX IDX_8B63CBDD36B23978 (endpoint_group_name),
                INDEX IDX_8B63CBDD1ED93D47 (user_group_id),
                UNIQUE INDEX lookup_unique_idx (endpoint_group_name, user_group_id), PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );
        $this->addSql(/** @lang MySQL */
            'CREATE TABLE endpoint_user_access (
                id INT AUTO_INCREMENT NOT NULL,
                endpoint_id INT DEFAULT NULL,
                user_id BIGINT NOT NULL,
                relation VARCHAR(255) NOT NULL,
                INDEX IDX_84C2D67221AF7E36 (endpoint_id),
                INDEX IDX_84C2D672A76ED395 (user_id),
                UNIQUE INDEX lookup_unique_idx (endpoint_id, user_id), PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );
        $this->addSql(/** @lang MySQL */
            'CREATE TABLE endpoint_user_group_access (
                id INT AUTO_INCREMENT NOT NULL,
                endpoint_id INT DEFAULT NULL,
                user_group_id BIGINT NOT NULL,
                relation VARCHAR(255) NOT NULL,
                INDEX IDX_CC65D0A921AF7E36 (endpoint_id),
                INDEX IDX_CC65D0A91ED93D47 (user_group_id),
                UNIQUE INDEX lookup_unique_idx (endpoint_id, user_group_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );
        $this->addSql(/** @lang MySQL */
            'ALTER TABLE endpoint
                ADD CONSTRAINT FK_C4420F7B36B23978 FOREIGN KEY (endpoint_group_name)
                    REFERENCES endpoint_group (unique_group_name)'
        );
        $this->addSql(/** @lang MySQL */
            'ALTER TABLE endpoint_group_user_access
                ADD CONSTRAINT FK_DFC5708736B23978 FOREIGN KEY (endpoint_group_name)
                    REFERENCES endpoint_group (unique_group_name)'
        );
        $this->addSql(/** @lang MySQL */
            'ALTER TABLE endpoint_group_user_access
                ADD CONSTRAINT FK_DFC57087A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)'
        );
        $this->addSql(/** @lang MySQL */
            'ALTER TABLE endpoint_group_user_group_access
                ADD CONSTRAINT FK_8B63CBDD36B23978 FOREIGN KEY (endpoint_group_name)
                    REFERENCES endpoint_group (unique_group_name)'
        );
        $this->addSql(/** @lang MySQL */
            'ALTER TABLE endpoint_group_user_group_access
                ADD CONSTRAINT FK_8B63CBDD1ED93D47 FOREIGN KEY (user_group_id) REFERENCES user_groups (id)'
        );
        $this->addSql(/** @lang MySQL */
            'ALTER TABLE endpoint_user_access
                ADD CONSTRAINT FK_84C2D67221AF7E36 FOREIGN KEY (endpoint_id) REFERENCES endpoint (id)'
        );
        $this->addSql(/** @lang MySQL */
            'ALTER TABLE endpoint_user_access
                ADD CONSTRAINT FK_84C2D672A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)'
        );
        $this->addSql(/** @lang MySQL */
            'ALTER TABLE endpoint_user_group_access
                ADD CONSTRAINT FK_CC65D0A921AF7E36 FOREIGN KEY (endpoint_id) REFERENCES endpoint (id)'
        );
        $this->addSql(/** @lang MySQL */
            'ALTER TABLE endpoint_user_group_access
                ADD CONSTRAINT FK_CC65D0A91ED93D47 FOREIGN KEY (user_group_id) REFERENCES user_groups (id)'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */'ALTER TABLE endpoint_user_access DROP FOREIGN KEY FK_84C2D67221AF7E36');
        $this->addSql(/** @lang MySQL */'ALTER TABLE endpoint_user_group_access DROP FOREIGN KEY FK_CC65D0A921AF7E36');
        $this->addSql(/** @lang MySQL */'ALTER TABLE endpoint DROP FOREIGN KEY FK_C4420F7B36B23978');
        $this->addSql(/** @lang MySQL */
            'ALTER TABLE endpoint_group_user_access
                DROP FOREIGN KEY FK_DFC5708736B23978'
        );
        $this->addSql(/** @lang MySQL */
            'ALTER TABLE endpoint_group_user_group_access
                DROP FOREIGN KEY FK_8B63CBDD36B23978'
        );
        $this->addSql(/** @lang MySQL */'DROP TABLE endpoint');
        $this->addSql(/** @lang MySQL */'DROP TABLE endpoint_group');
        $this->addSql(/** @lang MySQL */'DROP TABLE endpoint_group_user_access');
        $this->addSql(/** @lang MySQL */'DROP TABLE endpoint_group_user_group_access');
        $this->addSql(/** @lang MySQL */'DROP TABLE endpoint_user_access');
        $this->addSql(/** @lang MySQL */'DROP TABLE endpoint_user_group_access');
    }
}
