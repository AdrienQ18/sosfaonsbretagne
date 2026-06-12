<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260612120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add webhook idempotency timestamps for donations and pre-orders.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE donation ADD receipt_email_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD association_notified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE pre_order ADD invoice_email_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD association_notified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE donation DROP receipt_email_sent_at, DROP association_notified_at');
        $this->addSql('ALTER TABLE pre_order DROP invoice_email_sent_at, DROP association_notified_at');
    }
}
