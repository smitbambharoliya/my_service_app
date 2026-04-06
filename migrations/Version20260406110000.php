<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add OTP security fields for rate limiting and expiration
 */
final class Version20260406110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add OTP rate limiting and expiration fields to user entity';
    }

    public function up(Schema $schema): void
    {
        // Add OTP attempts field (for rate limiting)
        $this->addSql('ALTER TABLE `user` ADD COLUMN otp_attempts INT NOT NULL DEFAULT 0');

        // Add OTP expiration timestamp
        $this->addSql('ALTER TABLE `user` ADD COLUMN otp_expires_at DATETIME NULL DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP COLUMN otp_attempts');
        $this->addSql('ALTER TABLE `user` DROP COLUMN otp_expires_at');
    }
}
