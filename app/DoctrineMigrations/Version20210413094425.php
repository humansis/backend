<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210413094425 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $bothOldAndNewSectors = [
            'food_security',
            'livelihoods',
            'multipurpose_cash',
            'shelter',
            'wash',
            'protection',
            'education',
            'emergency_telco',
            'health',
            'logistics',
            'nutrition',
            'mine',
            'drr_resilience',
            'non_sector',
            'camp_management',
            'early_recovery',
            'education_tvet',
        ];

        $newSectors = [
            'food_security',
            'livelihoods',
            'multipurpose_cash',
            'shelter',
            'wash',
            'protection',
            'emergency_telco',
            'health',
            'logistics',
            'nutrition',
            'mine',
            'drr_resilience',
            'non_sector',
            'camp_management',
            'early_recovery',
            'education_tvet',
        ];

        $bothOldAndNewSubSectors = [
            'food_distribution',
            'food_vouchers',
            'food_cash_grants',
            'food_cash_for_work',
            'skills_training',
            'technical_support',
            'provision_of_inputs',
            'distribution_of_inputs',
            'business_grants',
            'agricultural_vouchers',
            'livelihood_cash_for_work',
            'multi_purpose_cash_assistance',
            'rehabilitation',
            'construction',
            'settlement_upgrades',
            'winterization_kits',
            'winterization_upgrades',
            'shelter_kits',
            'nfi_kits',
            'cash_for_shelter',
            'water_point_rehabilitation',
            'water_point_construction',
            'water_trucking',
            'water_treatment',
            'vector_control',
            'solid_waste_management',
            'sanitation',
            'hygiene_promotion',
            'hygiene_kits',
            'operational_supplies',
            'protection_psychosocial_support',
            'individual_protection_assistance',
            'community_based_interventions',
            'protection_advocacy',
            'child_protection',
            'gender_based_violence_activities',
            'teacher_incentive_payments',
            'teacher_training',
            'learning_materials',
            'education_psychosocial_support',
            'education_services',
            'education_cash_for_work',
            'education_parent_sessions',
            'default_emergency_telco',
            'default_health',
            'default_logistics',
            'default_nutrition',
            'default_mine',
            'default_drr_resilience',
            'default_non_sector',
            'default_camp_management',
            'default_early_recovery',
            'in_kind_food',
            'food_cash_transfers',
            'learning_support',
            'school_operational_support',
        ];

        $newSubSectors = [
            'food_vouchers',
            'food_cash_for_work',
            'skills_training',
            'technical_support',
            'provision_of_inputs',
            'business_grants',
            'agricultural_vouchers',
            'livelihood_cash_for_work',
            'multi_purpose_cash_assistance',
            'rehabilitation',
            'construction',
            'settlement_upgrades',
            'winterization_kits',
            'winterization_upgrades',
            'shelter_kits',
            'nfi_kits',
            'cash_for_shelter',
            'water_point_rehabilitation',
            'water_point_construction',
            'water_trucking',
            'water_treatment',
            'vector_control',
            'solid_waste_management',
            'sanitation',
            'hygiene_promotion',
            'hygiene_kits',
            'operational_supplies',
            'protection_psychosocial_support',
            'individual_protection_assistance',
            'community_based_interventions',
            'protection_advocacy',
            'child_protection',
            'gender_based_violence_activities',
            'teacher_incentive_payments',
            'teacher_training',
            'learning_materials',
            'education_psychosocial_support',
            'education_cash_for_work',
            'education_parent_sessions',
            'default_emergency_telco',
            'default_health',
            'default_logistics',
            'default_nutrition',
            'default_mine',
            'default_drr_resilience',
            'default_non_sector',
            'default_camp_management',
            'default_early_recovery',
            'in_kind_food',
            'food_cash_transfers',
            'learning_support',
            'school_operational_support',
        ];

        $this->addSql("ALTER TABLE assistance CHANGE sector sector ENUM('" . implode('\',\'', $bothOldAndNewSectors) . "')");
        $this->addSql("UPDATE assistance SET sector='education_tvet' WHERE sector='education'");
        $this->addSql("ALTER TABLE assistance CHANGE sector sector ENUM('" . implode('\',\'', $newSectors) . "')");

        $this->addSql("ALTER TABLE assistance CHANGE subsector subsector ENUM('" . implode('\',\'', $bothOldAndNewSubSectors) . "')");
        $this->addSql("UPDATE assistance SET subsector='in-kind-food' WHERE subsector='food_distribution'");
        $this->addSql("UPDATE assistance SET subsector='food_cash_transfers' WHERE subsector='food_cash_grants'");
        $this->addSql("UPDATE assistance SET subsector='provision_of_inputs' WHERE subsector='distribution_of_inputs'");
        $this->addSql("UPDATE assistance SET subsector='learning_support' WHERE subsector='education_services'");
        $this->addSql("ALTER TABLE assistance CHANGE subsector subsector ENUM('" . implode('\',\'', $newSubSectors) . "')");

        $this->addSql("ALTER TABLE project_sector CHANGE sector sector ENUM('" . implode('\',\'', $bothOldAndNewSectors) . "')");
        $this->addSql("UPDATE project_sector SET sector='education_tvet' WHERE sector='education'");
        $this->addSql("ALTER TABLE project_sector CHANGE sector sector ENUM('" . implode('\',\'', $newSectors) . "')");

        $this->addSql("ALTER TABLE project_sector CHANGE subsector subsector ENUM('" . implode('\',\'', $bothOldAndNewSubSectors) . "')");
        $this->addSql("UPDATE project_sector SET subsector='in-kind-food' WHERE subsector='food_distribution'");
        $this->addSql("UPDATE project_sector SET subsector='food_cash_transfers' WHERE subsector='food_cash_grants'");
        $this->addSql("UPDATE project_sector SET subsector='provision_of_inputs' WHERE subsector='distribution_of_inputs'");
        $this->addSql("UPDATE project_sector SET subsector='learning_support' WHERE subsector='education_services'");
        $this->addSql("ALTER TABLE project_sector CHANGE subsector subsector ENUM('" . implode('\',\'', $newSubSectors) . "')");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
