<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201029153221 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'ALTER TABLE assistance
            ADD sector ENUM(
                \'food_security\', \'livelihoods\', \'multipurpose_cash\', \'shelter\', \'wash\', \'protection\',
                \'education\', \'emergency_telco\', \'health\', \'logistics\', \'nutrition\', \'mine\',
                \'drr_resilience\', \'non_sector\', \'camp_management\', \'early_recovery\'
            ) NOT NULL COMMENT \'(DC2Type:enum_sector)\',
            ADD subsector ENUM(
                \'food_parcels_baskets\', \'rter\', \'food_vouchers\', \'cash_for_work\', \'skills_training\', \'technical_support\',
                \'distribution_of_inputs\', \'business_grants\', \'agricultural_vouchers\', \'multi_purpose_cash_assistance\',
                \'rehabilitation\', \'construction\', \'settlement_upgrades\', \'winterization_kits\', \'winterization_upgrades\',
                \'shelter_kits\', \'nfi_kits\', \'cash_for_shelter\', \'water_point_rehabilitation\',
                \'water_point_construction\', \'water_trucking\', \'water_treatment\', \'vector_control\', \'solid_waste_management\',
                \'sanitation\', \'hygiene_promotion\', \'hygiene_kits\', \'operational_supplies\', \'protection_psychosocial_support\',
                \'individual_protection_assistance\', \'community_based_interventions\', \'protection_advocacy\', \'child_protection\',
                \'gender_based_violence_activities\', \'teacher_incentive_payments\', \'teacher_training\', \'learning_materials\',
                \'education_psychosocial_support\', \'education_services\'
            ) DEFAULT NULL COMMENT \'(DC2Type:enum_sub_sector)\''
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE assistance DROP sector, DROP subsector');
    }
}
