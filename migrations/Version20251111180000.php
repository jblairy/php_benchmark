<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add warmup_iterations and inner_iterations columns to benchmarks table.
 */
final class Version20251111180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add warmup_iterations and inner_iterations columns to benchmarks table for per-benchmark iteration configuration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE benchmarks ADD warmup_iterations INT DEFAULT NULL');
        $this->addSql('ALTER TABLE benchmarks ADD inner_iterations INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE benchmarks DROP warmup_iterations');
        $this->addSql('ALTER TABLE benchmarks DROP inner_iterations');
    }
}