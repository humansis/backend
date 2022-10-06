<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200805123128 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'CREATE TABLE abstract_beneficiary (id INT AUTO_INCREMENT NOT NULL, bnf_type varchar(255) NOT NULL, UNIQUE INDEX UNIQ_7ABF446ACCFA12B8 (id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        // create helper columns
        $this->addSql(
            'ALTER TABLE abstract_beneficiary ADD source_hh_id INT DEFAULT NULL, ADD source_bnf_id INT DEFAULT NULL, ADD source_institution_id INT DEFAULT NULL, ADD source_community_id INT DEFAULT NULL'
        );
        $this->addSql('ALTER TABLE abstract_beneficiary ADD CONSTRAINT `FK_111` FOREIGN KEY (`source_hh_id`) REFERENCES `household`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE; ');
        $this->addSql('ALTER TABLE abstract_beneficiary ADD CONSTRAINT `FK_222` FOREIGN KEY (`source_bnf_id`) REFERENCES `beneficiary`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE; ');
        $this->addSql('ALTER TABLE abstract_beneficiary ADD CONSTRAINT `FK_333` FOREIGN KEY (`source_institution_id`) REFERENCES `institution`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE; ');
        $this->addSql('ALTER TABLE abstract_beneficiary ADD CONSTRAINT `FK_444` FOREIGN KEY (`source_community_id`) REFERENCES `community`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE; ');

        // copy data to abstract bnf
        $this->addSql('INSERT INTO abstract_beneficiary (bnf_type, source_hh_id) SELECT "hh", id FROM household;');
        $this->addSql('INSERT INTO abstract_beneficiary (bnf_type, source_bnf_id) SELECT "bnf", id FROM beneficiary;');
        $this->addSql('INSERT INTO abstract_beneficiary (bnf_type, source_institution_id) SELECT "inst", id FROM institution;');
        $this->addSql('INSERT INTO abstract_beneficiary (bnf_type, source_community_id) SELECT "comm", id FROM community;');

        // free FK to HH table (set to CASCADE)
        $this->addSql(
            'ALTER TABLE `household_project` DROP FOREIGN KEY `FK_42473AC0E79FF843`; ALTER TABLE `household_project` ADD CONSTRAINT `FK_42473AC0E79FF843` FOREIGN KEY (`household_id`) REFERENCES `household`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;'
        );
        $this->addSql(
            'ALTER TABLE `country_specific_answer` DROP FOREIGN KEY `FK_4680BB30E79FF843`; ALTER TABLE `country_specific_answer` ADD CONSTRAINT `FK_4680BB30E79FF843` FOREIGN KEY (`household_id`) REFERENCES `household`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;'
        );
        $this->addSql(
            'ALTER TABLE `household_location` DROP FOREIGN KEY `FK_822570EEE79FF843`; ALTER TABLE `household_location` ADD CONSTRAINT `FK_822570EEE79FF843` FOREIGN KEY (`household_id`) REFERENCES `household`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;'
        );
        $this->addSql(
            'ALTER TABLE `beneficiary` DROP FOREIGN KEY `FK_7ABF446AE79FF843`; ALTER TABLE `beneficiary` ADD CONSTRAINT `FK_7ABF446AE79FF843` FOREIGN KEY (`household_id`) REFERENCES `household`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;'
        );
        $this->addSql(
            'ALTER TABLE `household_activity` DROP FOREIGN KEY `FK_4A4E9A65E79FF843`; ALTER TABLE `household_activity` ADD CONSTRAINT `FK_4A4E9A65E79FF843` FOREIGN KEY (`household_id`) REFERENCES `household`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE; '
        );
        // prevent ids collision
        $this->addSql('UPDATE household SET id=id+(SELECT count(id) from abstract_beneficiary)');
        // edit HH ids to abstract_bnf ids
        $this->addSql('UPDATE household hh INNER JOIN abstract_beneficiary ab ON hh.id=ab.source_hh_id SET hh.id=ab.id WHERE ab.source_hh_id is not null');
        // restrict FK to HH table (set to RESTRICT)
        $this->addSql(
            'ALTER TABLE `household_activity` DROP FOREIGN KEY `FK_4A4E9A65E79FF843`; ALTER TABLE `household_activity` ADD CONSTRAINT `FK_4A4E9A65E79FF843` FOREIGN KEY (`household_id`) REFERENCES `household`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT; '
        );
        $this->addSql(
            'ALTER TABLE `household_location` DROP FOREIGN KEY `FK_822570EEE79FF843`; ALTER TABLE `household_location` ADD CONSTRAINT `FK_822570EEE79FF843` FOREIGN KEY (`household_id`) REFERENCES `household`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT; '
        );
        $this->addSql(
            'ALTER TABLE `household_project` DROP FOREIGN KEY `FK_42473AC0E79FF843`; ALTER TABLE `household_project` ADD CONSTRAINT `FK_42473AC0E79FF843` FOREIGN KEY (`household_id`) REFERENCES `household`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; '
        );
        $this->addSql(
            'ALTER TABLE `country_specific_answer` DROP FOREIGN KEY `FK_4680BB30E79FF843`; ALTER TABLE `country_specific_answer` ADD CONSTRAINT `FK_4680BB30E79FF843` FOREIGN KEY (`household_id`) REFERENCES `household`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT; '
        );
        $this->addSql(
            'ALTER TABLE `beneficiary` DROP FOREIGN KEY `FK_7ABF446AE79FF843`; ALTER TABLE `beneficiary` ADD CONSTRAINT `FK_7ABF446AE79FF843` FOREIGN KEY (`household_id`) REFERENCES `household`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT; '
        );

        // free FK to BNF table (set to CASCADE)
        $this->addSql(
            'ALTER TABLE `beneficiary_vulnerability_criterion` DROP FOREIGN KEY `FK_566B5C7ECCAAFA0`; ALTER TABLE `beneficiary_vulnerability_criterion` ADD CONSTRAINT `FK_566B5C7ECCAAFA0` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiary`(`id`) ON DELETE CASCADE ON UPDATE CASCADE; '
        );
        $this->addSql(
            'ALTER TABLE `distribution_beneficiary` DROP FOREIGN KEY `FK_EA141F30ECCAAFA0`; ALTER TABLE `distribution_beneficiary` ADD CONSTRAINT `FK_EA141F30ECCAAFA0` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiary`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE; '
        );
        $this->addSql(
            'ALTER TABLE `smartcard` DROP FOREIGN KEY `FK_34E0B48FECCAAFA0`; ALTER TABLE `smartcard` ADD CONSTRAINT `FK_34E0B48FECCAAFA0` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiary`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE; '
        );
        // prevent ids collision
        $this->addSql('UPDATE beneficiary SET id=id+(SELECT count(id) from abstract_beneficiary)');
        // edit BNF ids to abstract_bnf ids
        $this->addSql('UPDATE beneficiary bnf INNER JOIN abstract_beneficiary ab ON bnf.id=ab.source_bnf_id SET bnf.id=ab.id WHERE ab.source_bnf_id is not null;');
        // restrict FK to BNF table (set to RESTRICT)
        $this->addSql(
            'ALTER TABLE `smartcard` DROP FOREIGN KEY `FK_34E0B48FECCAAFA0`; ALTER TABLE `smartcard` ADD CONSTRAINT `FK_34E0B48FECCAAFA0` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiary`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT; '
        );
        $this->addSql(
            'ALTER TABLE `distribution_beneficiary` DROP FOREIGN KEY `FK_EA141F30ECCAAFA0`; ALTER TABLE `distribution_beneficiary` ADD CONSTRAINT `FK_EA141F30ECCAAFA0` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiary`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT; '
        );
        $this->addSql(
            'ALTER TABLE `beneficiary_vulnerability_criterion` DROP FOREIGN KEY `FK_566B5C7ECCAAFA0`; ALTER TABLE `beneficiary_vulnerability_criterion` ADD CONSTRAINT `FK_566B5C7ECCAAFA0` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiary`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; '
        );

        // free FK to INSTITUTION table (set to CASCADE)
        // -
        // prevent ids collision
        $this->addSql('UPDATE institution SET id=id+(SELECT count(id) from abstract_beneficiary);');
        // edit INSTITUTION ids to abstract_bnf ids
        $this->addSql('UPDATE institution INNER JOIN abstract_beneficiary ab ON institution.id=ab.source_institution_id SET institution.id=ab.id WHERE ab.source_institution_id is not null;');
        // restrict FK to INSTITUTION table (set to RESTRICT)
        // -

        // free FK to COMMUNITY table (set to CASCADE)
        // -
        // prevent ids collision
        $this->addSql('UPDATE community SET id=id+(SELECT count(id) from abstract_beneficiary);');
        // edit COMMUNITY ids to abstract_bnf ids
        $this->addSql('UPDATE community INNER JOIN abstract_beneficiary ab ON community.id=ab.source_community_id SET community.id=ab.id WHERE ab.source_community_id is not null;');
        // restrict FK to COMMUNITY table (set to RESTRICT)
        // -

        // remove helper columns
        $this->addSql('ALTER TABLE abstract_beneficiary DROP FOREIGN KEY FK_111');
        $this->addSql('ALTER TABLE abstract_beneficiary DROP FOREIGN KEY FK_222');
        $this->addSql('ALTER TABLE abstract_beneficiary DROP FOREIGN KEY FK_333');
        $this->addSql('ALTER TABLE abstract_beneficiary DROP FOREIGN KEY FK_444');
        $this->addSql('ALTER TABLE abstract_beneficiary DROP source_hh_id, DROP source_bnf_id, DROP source_institution_id, DROP source_community_id');

        // connect bnfs to abstract bnf
        // $this->addSql('ALTER TABLE household CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE household ADD CONSTRAINT FK_54C32FC0BF396750 FOREIGN KEY (id) REFERENCES abstract_beneficiary (id) ON DELETE CASCADE');
        // $this->addSql('ALTER TABLE beneficiary CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE beneficiary ADD CONSTRAINT FK_7ABF446ABF396750 FOREIGN KEY (id) REFERENCES abstract_beneficiary (id) ON DELETE CASCADE');
        // $this->addSql('ALTER TABLE community CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE community ADD CONSTRAINT FK_1B604033BF396750 FOREIGN KEY (id) REFERENCES abstract_beneficiary (id) ON DELETE CASCADE');
        // $this->addSql('ALTER TABLE institution CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE institution ADD CONSTRAINT FK_3A9F98E5BF396750 FOREIGN KEY (id) REFERENCES abstract_beneficiary (id) ON DELETE CASCADE');

        // logs contains IDs of (old) beneficiaries, so it is necessary to remove them
        $this->addSql('DELETE FROM `logs` WHERE 1=1 ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('DROP TABLE abstract_beneficiary');
    }
}
