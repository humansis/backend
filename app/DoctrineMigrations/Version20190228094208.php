<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190228094208 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE general_relief_item (id INT AUTO_INCREMENT NOT NULL, distribution_beneficiary_id INT DEFAULT NULL, distributedAt DATETIME DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, INDEX IDX_8BB0ED2795AAFAA9 (distribution_beneficiary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE general_relief_item ADD CONSTRAINT FK_8BB0ED2795AAFAA9 FOREIGN KEY (distribution_beneficiary_id) REFERENCES distribution_beneficiary (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE general_relief_item');
    }
}
