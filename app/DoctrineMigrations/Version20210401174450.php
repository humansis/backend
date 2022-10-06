<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210401174450 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // reset db attibutes to be same as doctrine definitions
        $this->addSql(
            'ALTER TABLE project_sector
                            CHANGE project_id project_id INT DEFAULT NULL,
                            CHANGE sector sector ENUM(\'food_security\', \'livelihoods\', \'multipurpose_cash\', \'shelter\', \'wash\', \'protection\', \'education\', \'emergency_telco\', \'health\', \'logistics\', \'nutrition\', \'mine\', \'drr_resilience\', \'non_sector\', \'camp_management\', \'early_recovery\') NOT NULL COMMENT \'(DC2Type:enum_sector)\''
        );
        $this->addSql('ALTER TABLE vulnerability_criterion CHANGE active active TINYINT(1) NOT NULL');
        $this->addSql(
            'ALTER TABLE assistance
                            DROP FOREIGN KEY FK_A54E7FD7166D1F9C,
                            DROP FOREIGN KEY FK_A54E7FD764D218E,
                            CHANGE assistance_type assistance_type ENUM(\'activity\', \'distribution\') NOT NULL COMMENT \'(DC2Type:enum_assistance_type)\''
        );
        $this->addSql('ALTER TABLE selection_criteria CHANGE group_number group_number INT NOT NULL');
        $this->addSql('ALTER TABLE smartcard_redemption_batch CHANGE currency currency VARCHAR(255) DEFAULT NULL');

        // reset id definition <= need to reset also foreign key
        $this->addSql('ALTER TABLE institution DROP CONSTRAINT FK_3A9F98E5BF396750');
        $this->addSql(
            'ALTER TABLE institution
                            CHANGE id id INT NOT NULL,
                            ADD CONSTRAINT FK_3A9F98E5BF396750 FOREIGN KEY (id) REFERENCES abstract_beneficiary (id) ON DELETE CASCADE'
        );

        $this->addSql('ALTER TABLE community DROP CONSTRAINT FK_1B604033BF396750');
        $this->addSql(
            'ALTER TABLE community
                            CHANGE id id INT NOT NULL,
                            ADD CONSTRAINT FK_1B604033BF396750 FOREIGN KEY (id) REFERENCES abstract_beneficiary (id) ON DELETE CASCADE'
        );

        $this->addSql('ALTER TABLE beneficiary DROP CONSTRAINT FK_7ABF446ABF396750');
        $this->addSql('ALTER TABLE beneficiary_vulnerability_criterion DROP CONSTRAINT FK_566B5C7ECCAAFA0');
        $this->addSql('ALTER TABLE smartcard DROP CONSTRAINT FK_34E0B48FECCAAFA0');
        $this->addSql(
            'ALTER TABLE beneficiary
                            CHANGE id id INT NOT NULL,
                            ADD CONSTRAINT FK_7ABF446ABF396750 FOREIGN KEY (id) REFERENCES abstract_beneficiary (id) ON DELETE CASCADE'
        );
        $this->addSql('ALTER TABLE beneficiary_vulnerability_criterion ADD CONSTRAINT FK_566B5C7ECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE smartcard ADD CONSTRAINT FK_34E0B48FECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id)');

        $this->addSql('ALTER TABLE household DROP CONSTRAINT FK_54C32FC0BF396750');
        $this->addSql('ALTER TABLE household_activity DROP CONSTRAINT FK_4A4E9A65E79FF843');
        $this->addSql('ALTER TABLE household_location DROP CONSTRAINT FK_822570EEE79FF843');
        $this->addSql('ALTER TABLE country_specific_answer DROP CONSTRAINT FK_4680BB30E79FF843');
        $this->addSql('ALTER TABLE beneficiary DROP CONSTRAINT FK_7ABF446AE79FF843');
        $this->addSql(
            'ALTER TABLE household
                            CHANGE id id INT NOT NULL,
                            ADD CONSTRAINT FK_54C32FC0BF396750 FOREIGN KEY (id) REFERENCES abstract_beneficiary (id) ON DELETE CASCADE'
        );
        $this->addSql('ALTER TABLE household_activity ADD CONSTRAINT FK_4A4E9A65E79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE RESTRICT ON UPDATE RESTRICT');
        $this->addSql('ALTER TABLE household_location ADD CONSTRAINT FK_822570EEE79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE RESTRICT ON UPDATE RESTRICT');
        $this->addSql('ALTER TABLE country_specific_answer ADD CONSTRAINT FK_4680BB30E79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE RESTRICT ON UPDATE RESTRICT');
        $this->addSql('ALTER TABLE beneficiary ADD CONSTRAINT FK_7ABF446AE79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE RESTRICT ON UPDATE RESTRICT');
    }

    public function down(Schema $schema): void
    {
    }
}
