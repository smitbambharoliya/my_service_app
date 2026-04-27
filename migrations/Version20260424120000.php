<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add database indexes for query performance optimization
 */
final class Version20260424120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add database indexes for query performance optimization';
    }

    public function up(Schema $schema): void
    {
        // Add indexes for frequently queried fields
        
        // Service indexes
        if (!$this->indexExists($schema, 'service', 'IDX_SERVICE_PROVIDER')) {
            $this->addSql('CREATE INDEX IDX_SERVICE_PROVIDER ON service (provider_id)');
        }
        if (!$this->indexExists($schema, 'service', 'IDX_SERVICE_CATEGORY')) {
            $this->addSql('CREATE INDEX IDX_SERVICE_CATEGORY ON service (category_id)');
        }
        if (!$this->indexExists($schema, 'service', 'IDX_SERVICE_IS_ACTIVE')) {
            $this->addSql('CREATE INDEX IDX_SERVICE_IS_ACTIVE ON service (is_active)');
        }
        if (!$this->indexExists($schema, 'service', 'IDX_SERVICE_IS_PREMIUM')) {
            $this->addSql('CREATE INDEX IDX_SERVICE_IS_PREMIUM ON service (is_premium)');
        }

        // Booking indexes
        if (!$this->indexExists($schema, 'booking', 'IDX_BOOKING_CUSTOMER')) {
            $this->addSql('CREATE INDEX IDX_BOOKING_CUSTOMER ON booking (customer_id)');
        }
        if (!$this->indexExists($schema, 'booking', 'IDX_BOOKING_SERVICE')) {
            $this->addSql('CREATE INDEX IDX_BOOKING_SERVICE ON booking (service_id)');
        }
        if (!$this->indexExists($schema, 'booking', 'IDX_BOOKING_STATUS')) {
            $this->addSql('CREATE INDEX IDX_BOOKING_STATUS ON booking (status)');
        }
        if (!$this->indexExists($schema, 'booking', 'IDX_BOOKING_DATE')) {
            $this->addSql('CREATE INDEX IDX_BOOKING_DATE ON booking (booking_date)');
        }

        // Review indexes
        if (!$this->indexExists($schema, 'review', 'IDX_REVIEW_CUSTOMER')) {
            $this->addSql('CREATE INDEX IDX_REVIEW_CUSTOMER ON review (customer_id)');
        }
        if (!$this->indexExists($schema, 'review', 'IDX_REVIEW_PROVIDER')) {
            $this->addSql('CREATE INDEX IDX_REVIEW_PROVIDER ON review (provider_id)');
        }

        // User indexes
        if (!$this->indexExists($schema, 'user', 'IDX_USER_EMAIL')) {
            $this->addSql('CREATE INDEX IDX_USER_EMAIL ON `user` (email)');
        }
        if (!$this->indexExists($schema, 'user', 'IDX_USER_IS_VERIFIED')) {
            $this->addSql('CREATE INDEX IDX_USER_IS_VERIFIED ON `user` (is_verified)');
        }

        // Featured Service indexes
        if (!$this->indexExists($schema, 'featured_service', 'IDX_FEATURED_SERVICE_IS_ACTIVE')) {
            $this->addSql('CREATE INDEX IDX_FEATURED_SERVICE_IS_ACTIVE ON featured_service (is_active)');
        }

        // Message indexes
        if (!$this->indexExists($schema, 'message', 'IDX_MESSAGE_SENDER')) {
            $this->addSql('CREATE INDEX IDX_MESSAGE_SENDER ON message (sender_id)');
        }
        if (!$this->indexExists($schema, 'message', 'IDX_MESSAGE_RECIPIENT')) {
            $this->addSql('CREATE INDEX IDX_MESSAGE_RECIPIENT ON message (recipient_id)');
        }
    }

    public function down(Schema $schema): void
    {
        // Drop all indexes
        $this->addSql('DROP INDEX IDX_SERVICE_PROVIDER ON service');
        $this->addSql('DROP INDEX IDX_SERVICE_CATEGORY ON service');
        $this->addSql('DROP INDEX IDX_SERVICE_IS_ACTIVE ON service');
        $this->addSql('DROP INDEX IDX_SERVICE_IS_PREMIUM ON service');
        $this->addSql('DROP INDEX IDX_BOOKING_CUSTOMER ON booking');
        $this->addSql('DROP INDEX IDX_BOOKING_SERVICE ON booking');
        $this->addSql('DROP INDEX IDX_BOOKING_STATUS ON booking');
        $this->addSql('DROP INDEX IDX_BOOKING_DATE ON booking');
        $this->addSql('DROP INDEX IDX_REVIEW_CUSTOMER ON review');
        $this->addSql('DROP INDEX IDX_REVIEW_PROVIDER ON review');
        $this->addSql('DROP INDEX IDX_USER_EMAIL ON `user`');
        $this->addSql('DROP INDEX IDX_USER_IS_VERIFIED ON `user`');
        $this->addSql('DROP INDEX IDX_FEATURED_SERVICE_IS_ACTIVE ON featured_service');
        $this->addSql('DROP INDEX IDX_MESSAGE_SENDER ON message');
        $this->addSql('DROP INDEX IDX_MESSAGE_RECIPIENT ON message');
    }

    private function indexExists(Schema $schema, string $tableName, string $indexName): bool
    {
        $table = $schema->getTable($tableName);
        return $table->hasIndex($indexName);
    }
}
