<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220427181519 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE booklet ADD relief_package_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE booklet ADD CONSTRAINT FK_818DB720370C2938 FOREIGN KEY (relief_package_id) REFERENCES assistance_relief_package (id)');
        $this->addSql('CREATE INDEX IDX_818DB720370C2938 ON booklet (relief_package_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE booklet DROP FOREIGN KEY FK_818DB720370C2938');
        $this->addSql('DROP INDEX IDX_818DB720370C2938 ON booklet');
        $this->addSql('ALTER TABLE booklet DROP relief_package_id');
    }
}
