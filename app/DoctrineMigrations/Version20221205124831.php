<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221205124831 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $subSectors = [
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
            'cash_for_winterization',
            'cash_for_protection',
        ];

        $this->addSql("ALTER TABLE assistance CHANGE subsector subsector ENUM('" . implode('\',\'', $subSectors) . "')");
        $this->addSql("ALTER TABLE project_sector CHANGE subsector subsector ENUM('" . implode('\',\'', $subSectors) . "')");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'Downgrade is not supportedm');
    }
}
