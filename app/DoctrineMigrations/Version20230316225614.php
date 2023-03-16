<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230316225614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE assistance ADD closed_by_id INT DEFAULT NULL, CHANGE sector sector ENUM(\'Food Security\', \'Livelihoods\', \'Multi Purpose Cash Assistance\', \'Shelter\', \'WASH\', \'Protection\', \'Education & TVET\', \'Emergency Telecomms\', \'Health\', \'Logistics\', \'Nutrition\', \'Mine Action\', \'DRR & Resilience\', \'Non-Sector Specific\', \'Camp Coordination and Management\', \'Early Recovery\') NOT NULL COMMENT \'(DC2Type:enum_sector)\', CHANGE subsector subsector ENUM(\'in_kind_food\', \'food_cash_transfers\', \'food_vouchers\', \'food_cash_for_work\', \'skills_training\', \'technical_support\', \'provision_of_inputs\', \'business_grants\', \'agricultural_vouchers\', \'livelihood_cash_for_work\', \'multi_purpose_cash_assistance\', \'rehabilitation\', \'construction\', \'settlement_upgrades\', \'winterization_kits\', \'winterization_upgrades\', \'shelter_kits\', \'nfi_kits\', \'cash_for_shelter\', \'cash_for_winterization\', \'water_point_rehabilitation\', \'water_point_construction\', \'water_trucking\', \'water_treatment\', \'vector_control\', \'solid_waste_management\', \'sanitation\', \'hygiene_promotion\', \'hygiene_kits\', \'operational_supplies\', \'protection_psychosocial_support\', \'individual_protection_assistance\', \'community_based_interventions\', \'protection_advocacy\', \'child_protection\', \'gender_based_violence_activities\', \'cash_for_protection\', \'teacher_incentive_payments\', \'teacher_training\', \'learning_materials\', \'education_psychosocial_support\', \'learning_support\', \'education_cash_for_work\', \'education_parent_sessions\', \'school_operational_support\', \'default_emergency_telco\', \'default_health\', \'default_logistics\', \'default_nutrition\', \'default_mine\', \'default_drr_resilience\', \'default_non_sector\', \'default_camp_management\', \'default_early_recovery\') DEFAULT NULL COMMENT \'(DC2Type:enum_sub_sector)\', CHANGE food_limit food_limit NUMERIC(10, 0) DEFAULT NULL, CHANGE non_food_limit non_food_limit NUMERIC(10, 0) DEFAULT NULL, CHANGE cashback_limit cashback_limit NUMERIC(10, 0) DEFAULT NULL');
        $this->addSql('ALTER TABLE assistance ADD CONSTRAINT FK_1B4F85F2E1FA7797 FOREIGN KEY (closed_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_1B4F85F2E1FA7797 ON assistance (closed_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE assistance DROP closed_by_id, CHANGE sector sector VARCHAR(255) DEFAULT NULL, CHANGE subsector subsector VARCHAR(255) DEFAULT NULL, CHANGE food_limit food_limit NUMERIC(10, 2) DEFAULT NULL, CHANGE non_food_limit non_food_limit NUMERIC(10, 2) DEFAULT NULL, CHANGE cashback_limit cashback_limit NUMERIC(10, 2) DEFAULT NULL');
    }
}
