<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190702080035 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            '
            CREATE TABLE address (id INT AUTO_INCREMENT NOT NULL,
                location_id INT DEFAULT NULL,
                number VARCHAR(45) DEFAULT NULL,
                street VARCHAR(255) DEFAULT NULL,
                postcode VARCHAR(45) DEFAULT NULL,
                INDEX IDX_D4E6F8164D218E (location_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_D4E6F8164D218E FOREIGN KEY (location_id)
                    REFERENCES location (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            '
            CREATE TABLE camp (
                id INT AUTO_INCREMENT NOT NULL,
                location_id INT DEFAULT NULL,
                name VARCHAR(45) NOT NULL,
                INDEX IDX_C194423064D218E (location_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_C194423064D218E FOREIGN KEY (location_id)
                    REFERENCES location (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            '
            CREATE TABLE camp_address (
                id INT AUTO_INCREMENT NOT NULL,
                camp_id INT DEFAULT NULL,
                tentNumber VARCHAR(45) NOT NULL,
                INDEX IDX_7DDD2CEF77075ABB (camp_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_7DDD2CEF77075ABB FOREIGN KEY (camp_id)
                    REFERENCES camp (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            '
            CREATE TABLE household_location (
                id INT AUTO_INCREMENT NOT NULL,
                address_id INT DEFAULT NULL,
                camp_address_id INT DEFAULT NULL,
                household_id INT DEFAULT NULL,
                location_group VARCHAR(45) NOT NULL,
                type VARCHAR(45) NOT NULL,
                UNIQUE INDEX UNIQ_822570EEF5B7AF75 (address_id),
                UNIQUE INDEX UNIQ_822570EE5AC9717 (camp_address_id),
                INDEX IDX_822570EEE79FF843 (household_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_822570EEF5B7AF75 FOREIGN KEY (address_id)
                    REFERENCES address (id),
                CONSTRAINT FK_822570EE5AC9717 FOREIGN KEY (camp_address_id)
                    REFERENCES camp_address (id),
                CONSTRAINT FK_822570EEE79FF843 FOREIGN KEY (household_id)
                    REFERENCES household (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
    }

    public function postUp(Schema $schema): void
    {
        $households = $this->connection->fetchAll('SELECT id, address_street, address_number, address_postcode, location_id FROM household');
        foreach ($households as $household) {
            $this->connection->insert(
                'address',
                [
                    'location_id' => $household['location_id'],
                    'number' => $household['address_number'],
                    'street' => $household['address_street'],
                    'postcode' => $household['address_postcode'],
                ]
            );

            $max = $this->connection->fetchAssoc('SELECT MAX(id) as max FROM address');

            $this->connection->insert(
                'household_location',
                [
                    'address_id' => $max['max'],
                    'household_id' => $household['id'],
                    'location_group' => 'current',
                    'type' => 'residence',
                ]
            );
        }

        // Drop tables
        $this->connection->executeQuery('ALTER TABLE household DROP FOREIGN KEY FK_54C32FC064D218E');
        $this->connection->executeQuery('DROP INDEX IDX_54C32FC064D218E ON household');
        $this->connection->executeQuery('ALTER TABLE household DROP location_id, DROP address_street, DROP address_number, DROP address_postcode');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household_location DROP FOREIGN KEY FK_822570EEF5B7AF75');
        $this->addSql('ALTER TABLE household_location DROP FOREIGN KEY FK_822570EE5AC9717');
        $this->addSql('ALTER TABLE camp_address DROP FOREIGN KEY FK_7DDD2CEF77075ABB');
        $this->addSql('DROP TABLE household_location');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP TABLE camp_address');
        $this->addSql('DROP TABLE camp');
        $this->addSql(
            'ALTER TABLE household ADD location_id INT DEFAULT NULL, ADD address_street VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, ADD address_number VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, ADD address_postcode VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci'
        );
        $this->addSql('ALTER TABLE household ADD CONSTRAINT FK_54C32FC064D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('CREATE INDEX IDX_54C32FC064D218E ON household (location_id)');
    }
}
