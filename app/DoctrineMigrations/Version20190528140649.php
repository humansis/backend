<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190528140649 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE household_location (id INT AUTO_INCREMENT NOT NULL, address_id INT DEFAULT NULL, camp_address_id INT DEFAULT NULL, locationGroup VARCHAR(45) NOT NULL, type VARCHAR(45) NOT NULL, UNIQUE INDEX UNIQ_822570EEF5B7AF75 (address_id), UNIQUE INDEX UNIQ_822570EE5AC9717 (camp_address_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE camp_address (id INT AUTO_INCREMENT NOT NULL, camp_id INT DEFAULT NULL, tentNumber VARCHAR(45) NOT NULL, INDEX IDX_7DDD2CEF77075ABB (camp_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE camp (id INT AUTO_INCREMENT NOT NULL, location_id INT DEFAULT NULL, name VARCHAR(45) NOT NULL, INDEX IDX_C194423064D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE address (id INT AUTO_INCREMENT NOT NULL, location_id INT DEFAULT NULL, number VARCHAR(45) DEFAULT NULL, street VARCHAR(45) NOT NULL, postcode VARCHAR(45) NOT NULL, INDEX IDX_D4E6F8164D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE household_location ADD CONSTRAINT FK_822570EEF5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id)');
        $this->addSql('ALTER TABLE household_location ADD CONSTRAINT FK_822570EE5AC9717 FOREIGN KEY (camp_address_id) REFERENCES camp_address (id)');
        $this->addSql('ALTER TABLE camp_address ADD CONSTRAINT FK_7DDD2CEF77075ABB FOREIGN KEY (camp_id) REFERENCES camp (id)');
        $this->addSql('ALTER TABLE camp ADD CONSTRAINT FK_C194423064D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F8164D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE household ADD household_location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE household ADD CONSTRAINT FK_54C32FC0A10695A9 FOREIGN KEY (household_location_id) REFERENCES household_location (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_54C32FC0A10695A9 ON household (household_location_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household DROP FOREIGN KEY FK_54C32FC0A10695A9');
        $this->addSql('ALTER TABLE household_location DROP FOREIGN KEY FK_822570EE5AC9717');
        $this->addSql('ALTER TABLE camp_address DROP FOREIGN KEY FK_7DDD2CEF77075ABB');
        $this->addSql('ALTER TABLE household_location DROP FOREIGN KEY FK_822570EEF5B7AF75');
        $this->addSql('DROP TABLE household_location');
        $this->addSql('DROP TABLE camp_address');
        $this->addSql('DROP TABLE camp');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP INDEX UNIQ_54C32FC0A10695A9 ON household');
        $this->addSql('ALTER TABLE household DROP household_location_id');
    }
}
