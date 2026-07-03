<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260703081946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE login_failure_alert (id INT AUTO_INCREMENT NOT NULL, sent_at DATETIME NOT NULL, failure_count INT NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_750DB481A76ED395 (user_id), INDEX IDX_LOGIN_FAILURE_ALERT_USER_SENT_AT (user_id, sent_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE login_failure_alert ADD CONSTRAINT FK_750DB481A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE login_failure_alert DROP FOREIGN KEY FK_750DB481A76ED395');
        $this->addSql('DROP TABLE login_failure_alert');
    }
}
