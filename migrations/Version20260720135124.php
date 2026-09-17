<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720135124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute l\'identité professionnelle et le logo professionnel des praticiens.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE professional_profile (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, professional_name VARCHAR(120) DEFAULT NULL, legal_name VARCHAR(120) DEFAULT NULL, siret VARCHAR(14) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(35) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, city VARCHAR(120) DEFAULT NULL, website_url VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_E728A82A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE professional_logo (id INT AUTO_INCREMENT NOT NULL, professional_profile_id INT NOT NULL, image_name VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_F6CBA3CDFB976A01 (professional_profile_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE professional_profile ADD CONSTRAINT FK_PROFESSIONAL_PROFILE_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE professional_logo ADD CONSTRAINT FK_PROFESSIONAL_LOGO_PROFILE FOREIGN KEY (professional_profile_id) REFERENCES professional_profile (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE professional_logo DROP FOREIGN KEY FK_PROFESSIONAL_LOGO_PROFILE');
        $this->addSql('ALTER TABLE professional_profile DROP FOREIGN KEY FK_PROFESSIONAL_PROFILE_USER');
        $this->addSql('DROP TABLE professional_logo');
        $this->addSql('DROP TABLE professional_profile');
    }
}
