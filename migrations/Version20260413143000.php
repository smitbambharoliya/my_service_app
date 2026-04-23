<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add in-app notifications table and user notification preferences';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD booking_in_app_notifications TINYINT(1) NOT NULL DEFAULT 1, ADD booking_email_notifications TINYINT(1) NOT NULL DEFAULT 1, ADD message_in_app_notifications TINYINT(1) NOT NULL DEFAULT 1, ADD message_email_notifications TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, type VARCHAR(40) NOT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, url VARCHAR(255) DEFAULT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BF5476CAA76ED395 (user_id), INDEX notification_user_read_idx (user_id, is_read, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('DROP TABLE notification');
        $this->addSql('ALTER TABLE `user` DROP booking_in_app_notifications, DROP booking_email_notifications, DROP message_in_app_notifications, DROP message_email_notifications');
    }
}
