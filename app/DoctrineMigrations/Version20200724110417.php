<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200724110417 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // create person table
        $this->addSql(
            '
            CREATE TABLE person (
                id INT AUTO_INCREMENT NOT NULL,
                profile_id INT DEFAULT NULL,
                referral_id INT DEFAULT NULL,
                enGivenName VARCHAR(255) DEFAULT NULL,
                enFamilyName VARCHAR(255) DEFAULT NULL,
                localGivenName VARCHAR(255) DEFAULT NULL,
                localFamilyName VARCHAR(255) DEFAULT NULL,
                gender SMALLINT NULL, dateOfBirth DATE NULL,
                updated_on DATETIME DEFAULT NULL,
                UNIQUE INDEX UNIQ_34DCD176CCFA12B8 (profile_id),
                UNIQUE INDEX UNIQ_34DCD1763CCAA4B7 (referral_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_34DCD176CCFA12B8 FOREIGN KEY (profile_id)
                    REFERENCES profile (id),
                CONSTRAINT FK_34DCD1763CCAA4B7 FOREIGN KEY (referral_id)
                    REFERENCES referral (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );

        // edit bnf table
        $this->addSql('ALTER TABLE beneficiary DROP FOREIGN KEY FK_7ABF446A3CCAA4B7');
        $this->addSql('ALTER TABLE beneficiary DROP FOREIGN KEY FK_7ABF446ACCFA12B8');
        $this->addSql('DROP INDEX UNIQ_7ABF446ACCFA12B8 ON beneficiary');
        $this->addSql('DROP INDEX UNIQ_7ABF446A3CCAA4B7 ON beneficiary');
        $this->addSql('ALTER TABLE beneficiary ADD person_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE beneficiary ADD CONSTRAINT FK_7ABF446A217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7ABF446A217BBB47 ON beneficiary (person_id)');

        // edit phone
        $this->addSql('ALTER TABLE phone DROP FOREIGN KEY FK_444F97DDECCAAFA0');
        $this->addSql('DROP INDEX IDX_444F97DDECCAAFA0 ON phone');
        $this->addSql('ALTER TABLE phone CHANGE beneficiary_id person_id INT DEFAULT NULL');

        // edit national id
        $this->addSql('ALTER TABLE national_id DROP FOREIGN KEY FK_36491297ECCAAFA0');
        $this->addSql('DROP INDEX IDX_36491297ECCAAFA0 ON national_id');
        $this->addSql('ALTER TABLE national_id CHANGE beneficiary_id person_id INT DEFAULT NULL');

        // copy data from bnf to person
        $this->addSql(
            'INSERT INTO person (id, profile_id, referral_id, enGivenName, enFamilyName, localGivenName, localFamilyName, gender, dateOfBirth, updated_on) SELECT id, profile_id, referral_id, enGivenName, enFamilyName, localGivenName, localFamilyName, gender, dateOfBirth, updated_on FROM `beneficiary`'
        );
        $this->addSql('UPDATE beneficiary SET person_id=id');

        // clear bnf columns
        $this->addSql('ALTER TABLE beneficiary DROP profile_id, DROP referral_id, DROP localGivenName, DROP localFamilyName, DROP gender, DROP dateOfBirth, DROP enGivenName, DROP enFamilyName');

        $this->addSql('ALTER TABLE phone ADD CONSTRAINT FK_444F97DD217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('CREATE INDEX IDX_444F97DD217BBB47 ON phone (person_id)');

        $this->addSql('ALTER TABLE national_id ADD CONSTRAINT FK_36491297217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('CREATE INDEX IDX_36491297217BBB47 ON national_id (person_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE beneficiary DROP FOREIGN KEY FK_7ABF446A217BBB47');
        $this->addSql('DROP TABLE person');
        $this->addSql('DROP INDEX UNIQ_7ABF446A217BBB47 ON beneficiary');
        $this->addSql(
            'ALTER TABLE beneficiary ADD referral_id INT DEFAULT NULL, ADD localGivenName VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, ADD localFamilyName VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, ADD gender SMALLINT NOT NULL, ADD dateOfBirth DATE NOT NULL, ADD enGivenName VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, ADD enFamilyName VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, CHANGE person_id profile_id INT DEFAULT NULL'
        );
        $this->addSql('ALTER TABLE beneficiary ADD CONSTRAINT FK_7ABF446A3CCAA4B7 FOREIGN KEY (referral_id) REFERENCES referral (id)');
        $this->addSql('ALTER TABLE beneficiary ADD CONSTRAINT FK_7ABF446ACCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7ABF446ACCFA12B8 ON beneficiary (profile_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7ABF446A3CCAA4B7 ON beneficiary (referral_id)');
    }
}
