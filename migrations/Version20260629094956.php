<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260629094956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE login_failure_log (id INT AUTO_INCREMENT NOT NULL, username_attempted VARCHAR(255) NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent LONGTEXT DEFAULT NULL, occurred_at DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_624FB93AA76ED395 (user_id), INDEX IDX_LOGIN_FAILURE_USER_OCCURRED_AT (user_id, occurred_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE login_failure_log ADD CONSTRAINT FK_624FB93AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE login_failure_log DROP FOREIGN KEY FK_624FB93AA76ED395');
        $this->addSql('DROP TABLE login_failure_log');
    }
}
