<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180523122426 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_country (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, rights INT NOT NULL, iso3 VARCHAR(3) NOT NULL, INDEX IDX_B7ED76CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_project (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, project_id INT DEFAULT NULL, rights INT NOT NULL, INDEX IDX_77BECEE4A76ED395 (user_id), INDEX IDX_77BECEE4166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sector (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_donor (project_id INT NOT NULL, donor_id INT NOT NULL, INDEX IDX_C4A74909166D1F9C (project_id), INDEX IDX_C4A749093DD7B7A7 (donor_id), PRIMARY KEY(project_id, donor_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_sector (project_id INT NOT NULL, sector_id INT NOT NULL, INDEX IDX_5C0732A2166D1F9C (project_id), INDEX IDX_5C0732A2DE95C867 (sector_id), PRIMARY KEY(project_id, sector_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE donor (id INT AUTO_INCREMENT NOT NULL, fullname VARCHAR(255) NOT NULL, shortname VARCHAR(255) NOT NULL, dateAdded DATETIME NOT NULL, notes VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_country ADD CONSTRAINT FK_B7ED76CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user_project ADD CONSTRAINT FK_77BECEE4A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user_project ADD CONSTRAINT FK_77BECEE4166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_donor ADD CONSTRAINT FK_C4A74909166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_donor ADD CONSTRAINT FK_C4A749093DD7B7A7 FOREIGN KEY (donor_id) REFERENCES donor (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_sector ADD CONSTRAINT FK_5C0732A2166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_sector ADD CONSTRAINT FK_5C0732A2DE95C867 FOREIGN KEY (sector_id) REFERENCES sector (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_sector DROP FOREIGN KEY FK_5C0732A2DE95C867');
        $this->addSql('ALTER TABLE project_donor DROP FOREIGN KEY FK_C4A749093DD7B7A7');
        $this->addSql('DROP TABLE user_country');
        $this->addSql('DROP TABLE user_project');
        $this->addSql('DROP TABLE sector');
        $this->addSql('DROP TABLE project_donor');
        $this->addSql('DROP TABLE project_sector');
        $this->addSql('DROP TABLE donor');
    }
}
