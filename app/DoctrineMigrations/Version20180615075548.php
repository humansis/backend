<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180615075548 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE beneficiary_profile (beneficiary_id INT NOT NULL, profile_id INT NOT NULL, INDEX IDX_5C8EFFA5ECCAAFA0 (beneficiary_id), INDEX IDX_5C8EFFA5CCFA12B8 (profile_id), PRIMARY KEY(beneficiary_id, profile_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE profile (id INT AUTO_INCREMENT NOT NULL, photo VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE beneficiary_profile ADD CONSTRAINT FK_5C8EFFA5ECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE beneficiary_profile ADD CONSTRAINT FK_5C8EFFA5CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE beneficiary DROP photo');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE beneficiary_profile DROP FOREIGN KEY FK_5C8EFFA5CCFA12B8');
        $this->addSql('DROP TABLE beneficiary_profile');
        $this->addSql('DROP TABLE profile');
        $this->addSql('ALTER TABLE beneficiary ADD photo VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
    }
}
