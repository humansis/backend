<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201209161305 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $subsectorsEnum = "ENUM(
            'food_distribution',
            'food_vouchers',
            'food_cash_grants',
            'food_cash_for_work',

            'skills_training',
            'technical_support',
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
            'default_early_recovery'
        )";

        $this->addSql("ALTER TABLE project_sector ADD subsector_new $subsectorsEnum DEFAULT NULL COMMENT '(DC2Type:enum_sub_sector)'");
        $this->addSql("ALTER TABLE assistance ADD subsector_new $subsectorsEnum DEFAULT NULL COMMENT '(DC2Type:enum_sub_sector)'");

        $this->addSql("UPDATE assistance SET subsector_new=subsector WHERE subsector NOT IN ('food_parcels_baskets', 'rter', 'cash_for_work')");
        $this->addSql("UPDATE assistance SET subsector_new='food_distribution' WHERE subsector='food_parcels_baskets'");
        $this->addSql("UPDATE assistance SET subsector_new='food_cash_for_work' WHERE subsector='cash_for_work'");

        $this->addSql("UPDATE project_sector SET subsector_new=subsector WHERE subsector NOT IN ('food_parcels_baskets', 'rter', 'cash_for_work')");
        $this->addSql("UPDATE project_sector SET subsector_new='food_distribution' WHERE subsector='food_parcels_baskets'");
        $this->addSql("UPDATE project_sector SET subsector_new='food_cash_for_work' WHERE subsector='cash_for_work'");

        $this->addSql("ALTER TABLE project_sector DROP COLUMN subsector;");
        $this->addSql("ALTER TABLE assistance DROP COLUMN subsector;");

        $this->addSql("ALTER TABLE project_sector CHANGE subsector_new subsector $subsectorsEnum;");
        $this->addSql("ALTER TABLE assistance CHANGE subsector_new subsector $subsectorsEnum;");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'Migration missing. It is too complicated.');
    }
}
