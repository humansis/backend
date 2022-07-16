<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200812101228 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_sector DROP FOREIGN KEY FK_5C0732A2DE95C867');
        $this->addSql('ALTER TABLE project_sector DROP FOREIGN KEY FK_5C0732A2166D1F9C');
        $this->addSql('DROP INDEX IDX_5C0732A2DE95C867 ON project_sector');
        $this->addSql('ALTER TABLE project_sector ADD id INT AUTO_INCREMENT NOT NULL, ADD sector ENUM(\'camp_management\', \'early_recovery\', \'education\', \'emergency_telco\', \'food_security\', \'health\', \'logistics\', \'nutrition\', \'protection\', \'shelter\', \'cash_for_work\', \'tvet\', \'food_rte\', \'nfis\', \'wash\') NOT NULL COMMENT \'(DC2Type:enum_sector)\', DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE project_sector ADD CONSTRAINT FK_5C0732A2166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');

        $replaces = [
            'camp coordination and management' => 'camp_management',
            'early recovery' => 'early_recovery',
            'education' => 'education',
            'emergency telecommunications' => 'emergency_telco',
            'food security' => 'food_security',
            'health' => 'health',
            'logistics' => 'logistics',
            'nutrition' => 'nutrition',
            'protection' => 'protection',
            'shelter' => 'shelter',
            'cash for work' => 'cash_for_work',
            'TVET' => 'tvet',
            'food, RTE kits' => 'food_rte',
            'NFIs' => 'nfis',
            'WASH' => 'wash',
        ];
        foreach ($replaces as $name => $enumValue) {
            $this->addSql("UPDATE project_sector ps SET ps.sector='$enumValue' WHERE ps.sector_id IN (SELECT id FROM sector WHERE name = '$name');");
        }

        $this->addSql('CREATE UNIQUE INDEX uniq_sector_project ON project_sector (sector, project_id)');
        $this->addSql('ALTER TABLE project_sector DROP sector_id, CHANGE project_id project_id INT NOT NULL');
        $this->addSql('DROP TABLE sector');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_sector MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE project_sector DROP FOREIGN KEY FK_5C0732A2166D1F9C');
        $this->addSql('DROP INDEX uniq_sector_project ON project_sector');
        $this->addSql('ALTER TABLE project_sector DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE project_sector ADD sector_id INT NOT NULL, DROP id, DROP sector, CHANGE project_id project_id INT NOT NULL');
        $this->addSql('ALTER TABLE project_sector ADD CONSTRAINT FK_5C0732A2DE95C867 FOREIGN KEY (sector_id) REFERENCES sector (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_sector ADD CONSTRAINT FK_5C0732A2166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_5C0732A2DE95C867 ON project_sector (sector_id)');
        $this->addSql('ALTER TABLE project_sector ADD PRIMARY KEY (project_id, sector_id)');
    }
}
