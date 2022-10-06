<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200804111432 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE institution RENAME INDEX uniq_3a9f98e5e7a1254a TO UNIQ_3A9F98E54F8A983C');
        $this->addSql('ALTER TABLE community DROP FOREIGN KEY FK_1B604033E9E9E294');
        $this->addSql('DROP INDEX UNIQ_1B604033E9E9E294 ON community');
        $this->addSql('ALTER TABLE community ADD contact_person_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE community ADD CONSTRAINT FK_1B6040334F8A983C FOREIGN KEY (contact_person_id) REFERENCES person (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1B6040334F8A983C ON community (contact_person_id)');

        // copy data from institution to person
        $this->addSql('ALTER TABLE person ADD source_community_id INT DEFAULT NULL');
        $this->addSql('INSERT INTO person (source_community_id, enGivenName, enFamilyName) SELECT id, contact_name, contact_family_name FROM `community`;');
        $this->addSql('UPDATE community i INNER JOIN person p ON i.id=p.source_community_id SET i.contact_person_id=p.id WHERE p.source_community_id is not null');
        $this->addSql('ALTER TABLE person DROP source_community_id');

        // copy phone to its table
        $this->addSql('INSERT INTO phone (person_id, type, prefix, number, proxy) SELECT contact_person_id, "TYPE_community_CONTACT", phone_prefix, phone_number, 0 FROM `community`;');
        // connect national id to person
        $this->addSql('UPDATE national_id nid INNER JOIN community i ON i.national_id_id=nid.id SET nid.person_id=i.contact_person_id');

        $this->addSql('ALTER TABLE community DROP contact_name, DROP phone_number, DROP phone_prefix, DROP contact_family_name, DROP national_id_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE community DROP FOREIGN KEY FK_1B6040334F8A983C');
        $this->addSql('DROP INDEX UNIQ_1B6040334F8A983C ON community');
        $this->addSql(
            'ALTER TABLE community ADD contact_name VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, ADD phone_number VARCHAR(45) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, ADD phone_prefix VARCHAR(45) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, ADD contact_family_name VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, CHANGE contact_person_id national_id_id INT DEFAULT NULL'
        );
        $this->addSql('ALTER TABLE community ADD CONSTRAINT FK_1B604033E9E9E294 FOREIGN KEY (national_id_id) REFERENCES national_id (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1B604033E9E9E294 ON community (national_id_id)');
        $this->addSql('ALTER TABLE institution RENAME INDEX uniq_3a9f98e54f8a983c TO UNIQ_3A9F98E5E7A1254A');
    }
}
