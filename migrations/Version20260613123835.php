<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260613123835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE donation DROP INDEX idx_donation_email, ADD INDEX idx_donation_email (email)');
        $this->addSql('ALTER TABLE donation DROP INDEX idx_company_siret, ADD INDEX idx_company_siret (company_siret)');
        $this->addSql('ALTER TABLE donation ADD CONSTRAINT FK_31E581A0A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE pre_order ADD CONSTRAINT FK_EF82FC73A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE pre_order_item ADD CONSTRAINT FK_BAB46C87294869C FOREIGN KEY (article_id) REFERENCES article (id)');
        $this->addSql('ALTER TABLE pre_order_item ADD CONSTRAINT FK_BAB46C88B495F6B FOREIGN KEY (pre_order_id) REFERENCES pre_order (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6498E0E3CA6 FOREIGN KEY (user_role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE user_availability ADD CONSTRAINT FK_BF7BDEBDA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_availability ADD CONSTRAINT FK_BF7BDEBD61778466 FOREIGN KEY (availability_id) REFERENCES availability (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alert DROP FOREIGN KEY FK_17FD46C1A76ED395');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66A76ED395');
        $this->addSql('ALTER TABLE donation DROP INDEX idx_donation_email, ADD INDEX idx_donation_email (email(250))');
        $this->addSql('ALTER TABLE donation DROP INDEX idx_company_siret, ADD INDEX idx_company_siret (company_siret(250))');
        $this->addSql('ALTER TABLE donation DROP FOREIGN KEY FK_31E581A0A76ED395');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7A76ED395');
        $this->addSql('ALTER TABLE pre_order DROP FOREIGN KEY FK_EF82FC73A76ED395');
        $this->addSql('ALTER TABLE pre_order_item DROP FOREIGN KEY FK_BAB46C87294869C');
        $this->addSql('ALTER TABLE pre_order_item DROP FOREIGN KEY FK_BAB46C88B495F6B');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6498E0E3CA6');
        $this->addSql('ALTER TABLE user_availability DROP FOREIGN KEY FK_BF7BDEBDA76ED395');
        $this->addSql('ALTER TABLE user_availability DROP FOREIGN KEY FK_BF7BDEBD61778466');
    }
}
