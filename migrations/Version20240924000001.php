<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Translation tables for Gedmo Translatable Extension
 */
final class Version20240924000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create translation tables for multilingual content';
    }

    public function up(Schema $schema): void
    {
        // Create translation table for all translatable entities (PostgreSQL)
        $this->addSql('
            CREATE TABLE IF NOT EXISTS ext_translations (
                id SERIAL PRIMARY KEY,
                locale VARCHAR(8) NOT NULL,
                object_class VARCHAR(255) NOT NULL,
                field VARCHAR(32) NOT NULL,
                foreign_key VARCHAR(64) NOT NULL,
                content TEXT
            )
        ');
        
        $this->addSql('CREATE INDEX IF NOT EXISTS translations_lookup_idx ON ext_translations (locale, object_class, foreign_key)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS lookup_unique_idx ON ext_translations (locale, object_class, field, foreign_key)');

        // PostgreSQL version (comment out MySQL version above and use this if using PostgreSQL)
        /*
        $this->addSql('
            CREATE TABLE ext_translations (
                id SERIAL PRIMARY KEY,
                locale VARCHAR(8) NOT NULL,
                object_class VARCHAR(255) NOT NULL,
                field VARCHAR(32) NOT NULL,
                foreign_key VARCHAR(64) NOT NULL,
                content TEXT
            )
        ');
        
        $this->addSql('CREATE INDEX translations_lookup_idx ON ext_translations (locale, object_class, foreign_key)');
        $this->addSql('CREATE UNIQUE INDEX lookup_unique_idx ON ext_translations (locale, object_class, field, foreign_key)');
        */
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ext_translations');
    }
}
