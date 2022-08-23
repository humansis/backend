<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220715124701 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE adm2 DROP FOREIGN KEY FK_F58468EC93FDE579');
        $this->addSql('ALTER TABLE adm3 DROP FOREIGN KEY FK_8283587A81484A97');
        $this->addSql('ALTER TABLE adm4 DROP FOREIGN KEY FK_1CE7CDD939F42DF2');
        $this->addSql('DROP TABLE adm1');
        $this->addSql('DROP TABLE adm2');
        $this->addSql('DROP TABLE adm3');
        $this->addSql('DROP TABLE adm4');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE adm1 (id INT AUTO_INCREMENT NOT NULL, location_id INT DEFAULT NULL, name VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8_unicode_ci`, countryISO3 VARCHAR(3) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8_unicode_ci`, code VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8_unicode_ci`, UNIQUE INDEX UNIQ_6C8D395664D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE adm2 (id INT AUTO_INCREMENT NOT NULL, adm1_id INT DEFAULT NULL, location_id INT DEFAULT NULL, name VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8_unicode_ci`, code VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8_unicode_ci`, INDEX IDX_F58468EC93FDE579 (adm1_id), UNIQUE INDEX UNIQ_F58468EC64D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE adm3 (id INT AUTO_INCREMENT NOT NULL, adm2_id INT DEFAULT NULL, location_id INT DEFAULT NULL, name VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8_unicode_ci`, code VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8_unicode_ci`, INDEX IDX_8283587A81484A97 (adm2_id), UNIQUE INDEX UNIQ_8283587A64D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE adm4 (id INT AUTO_INCREMENT NOT NULL, adm3_id INT DEFAULT NULL, location_id INT DEFAULT NULL, name VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8_unicode_ci`, code VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8_unicode_ci`, INDEX IDX_1CE7CDD939F42DF2 (adm3_id), UNIQUE INDEX UNIQ_1CE7CDD964D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE adm1 ADD CONSTRAINT FK_6C8D395664D218E FOREIGN KEY (location_id) REFERENCES location (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE adm2 ADD CONSTRAINT FK_F58468EC64D218E FOREIGN KEY (location_id) REFERENCES location (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE adm2 ADD CONSTRAINT FK_F58468EC93FDE579 FOREIGN KEY (adm1_id) REFERENCES adm1 (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE adm3 ADD CONSTRAINT FK_8283587A64D218E FOREIGN KEY (location_id) REFERENCES location (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE adm3 ADD CONSTRAINT FK_8283587A81484A97 FOREIGN KEY (adm2_id) REFERENCES adm2 (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE adm4 ADD CONSTRAINT FK_1CE7CDD939F42DF2 FOREIGN KEY (adm3_id) REFERENCES adm3 (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE adm4 ADD CONSTRAINT FK_1CE7CDD964D218E FOREIGN KEY (location_id) REFERENCES location (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
