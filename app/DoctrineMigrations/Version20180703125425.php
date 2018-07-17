<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180703125425 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE beneficiary_profile');
        $this->addSql('ALTER TABLE beneficiary ADD profile_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE beneficiary ADD CONSTRAINT FK_7ABF446ACCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7ABF446ACCFA12B8 ON beneficiary (profile_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE beneficiary_profile (beneficiary_id INT NOT NULL, profile_id INT NOT NULL, INDEX IDX_5C8EFFA5ECCAAFA0 (beneficiary_id), INDEX IDX_5C8EFFA5CCFA12B8 (profile_id), PRIMARY KEY(beneficiary_id, profile_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE beneficiary_profile ADD CONSTRAINT FK_5C8EFFA5CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE beneficiary_profile ADD CONSTRAINT FK_5C8EFFA5ECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE beneficiary DROP FOREIGN KEY FK_7ABF446ACCFA12B8');
        $this->addSql('DROP INDEX UNIQ_7ABF446ACCFA12B8 ON beneficiary');
        $this->addSql('ALTER TABLE beneficiary DROP profile_id');
    }
}
