<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210425094651 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $mapping = [
            'food_security' => 'Food Security',
            'livelihoods' => 'Livelihoods',
            'multipurpose_cash' => 'Multi Purpose Cash Assistance',
            'shelter' => 'Shelter',
            'wash' => 'WASH',
            'protection' => 'Protection',
            'emergency_telco' => 'Emergency Telecomms',
            'health' => 'Health',
            'logistics' => 'Logistics',
            'nutrition' => 'Nutrition',
            'mine' => 'Mine Action',
            'drr_resilience' => 'DRR & Resilience',
            'non_sector' => 'Non-Sector Specific',
            'camp_management' => 'Camp Coordination and Management',
            'early_recovery' => 'Early Recovery',
            'education_tvet' => 'Education & TVET',
        ];

        $bothOldAndNewSectors = array_merge(array_keys($mapping), array_values($mapping));
        $newSectors = array_values($mapping);

        $this->addSql("ALTER TABLE assistance CHANGE sector sector ENUM('" . implode('\',\'', $bothOldAndNewSectors) . "') COLLATE utf8_bin");

        foreach ($mapping as $oldValue => $newValue) {
            $this->addSql("UPDATE assistance SET sector='$newValue' WHERE sector='$oldValue'");
        }

        $this->addSql("ALTER TABLE assistance CHANGE sector sector ENUM('" . implode('\',\'', $newSectors) . "') COLLATE utf8_unicode_ci");

        $this->addSql("ALTER TABLE project_sector CHANGE sector sector ENUM('" . implode('\',\'', $bothOldAndNewSectors) . "') COLLATE utf8_bin");

        foreach ($mapping as $oldValue => $newValue) {
            $this->addSql("UPDATE project_sector SET sector='$newValue' WHERE sector='$oldValue'");
        }

        $this->addSql("ALTER TABLE project_sector CHANGE sector sector ENUM('" . implode('\',\'', $newSectors) . "') COLLATE utf8_unicode_ci");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
