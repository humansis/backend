<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200814092226 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $subSectors = [];

        $subSectors[] = 'food_parcels_baskets';
        $subSectors[] = 'rter';
        $subSectors[] = 'food_vouchers';
        $subSectors[] = 'cash_for_work';

        $subSectors[] = 'skills_training';
        $subSectors[] = 'technical_support';
        $subSectors[] = 'distribution_of_inputs';
        $subSectors[] = 'business_grants';
        $subSectors[] = 'agricultural_vouchers';

        $subSectors[] = 'multi_purpose_cash_assistance';

        $subSectors[] = 'rehabilitation';
        $subSectors[] = 'construction';
        $subSectors[] = 'settlement_upgrades';
        $subSectors[] = 'winterization_kits';
        $subSectors[] = 'winterization_upgrades';
        $subSectors[] = 'shelter_kits';
        $subSectors[] = 'nfi_kits';
        $subSectors[] = 'cash_for_shelter';

        $subSectors[] = 'water_point_rehabilitation';
        $subSectors[] = 'water_point_construction';
        $subSectors[] = 'water_trucking';
        $subSectors[] = 'water_treatment';
        $subSectors[] = 'vector_control';
        $subSectors[] = 'solid_waste_management';
        $subSectors[] = 'sanitation';
        $subSectors[] = 'hygiene_promotion';
        $subSectors[] = 'hygiene_kits';
        $subSectors[] = 'operational_supplies';

        $subSectors[] = 'protection_psychosocial_support';
        $subSectors[] = 'individual_protection_assistance';
        $subSectors[] = 'community_based_interventions';
        $subSectors[] = 'protection_advocacy';
        $subSectors[] = 'child_protection';
        $subSectors[] = 'gender_based_violence_activities';

        $subSectors[] = 'teacher_incentive_payments';
        $subSectors[] = 'teacher_training';
        $subSectors[] = 'learning_materials';
        $subSectors[] = 'education_psychosocial_support';
        $subSectors[] = 'education_services';
        $subSectors = implode("', '", $subSectors);
        $this->addSql("ALTER TABLE project_sector ADD subsector ENUM('$subSectors') DEFAULT NULL COMMENT '(DC2Type:enum_sub_sector)'");
        $this->addSql('DROP INDEX uniq_sector_project ON project_sector');
        $this->addSql('CREATE UNIQUE INDEX uniq_sector_project ON project_sector (sector, subsector, project_id)');

        $sectorToSubSectorMapping = [
            'food_rte' => ['food_security', 'rter'],
            'cash_for_work' => ['food_security', 'cash_for_work'],
            'tvet' => ['education', 'education_services'],
        ];
        foreach ($sectorToSubSectorMapping as $oldSector => [$newSector, $newSubSector]) {
            $this->addSql("UPDATE project_sector ps SET ps.subsector='$newSubSector', ps.sector='$newSector' WHERE ps.sector = '$oldSector';");
        }
        $this->addSql("DELETE FROM project_sector WHERE sector = 'NFIs';");

        $sectors = [];
        $sectors[] = "food_security";
        $sectors[] = "livelihoods";
        $sectors[] = "multipurpose_cash";
        $sectors[] = "shelter";
        $sectors[] = "wash";
        $sectors[] = "protection";
        $sectors[] = "education";
        $sectors[] = "emergency_telco";
        $sectors[] = "health";
        $sectors[] = "logistics";
        $sectors[] = "nutrition";
        $sectors[] = "mine";
        $sectors[] = "drr_resilience";
        $sectors[] = "non_sector";
        $sectors[] = "camp_management";
        $sectors[] = "early_recovery";
        $sectors = implode("', '", $sectors);
        $this->addSql("ALTER TABLE project_sector CHANGE sector sector ENUM('$sectors') DEFAULT NULL COMMENT '(DC2Type:enum_sector)'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'CREATE TABLE sector (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' '
        );
        $this->addSql('ALTER TABLE project_sector DROP subsector, CHANGE project_id project_id INT NOT NULL');
    }
}
