<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210521113407 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE import_beneficiary_duplicity DROP FOREIGN KEY FK_CD6A7AF7DDA5A056');
        $this->addSql('ALTER TABLE import_beneficiary_duplicity ADD CONSTRAINT FK_CD6A7AF7DDA5A056 FOREIGN KEY (theirs_id) REFERENCES household (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE import_beneficiary_duplicity DROP FOREIGN KEY FK_CD6A7AF7DDA5A056');
        $this->addSql('ALTER TABLE import_beneficiary_duplicity ADD CONSTRAINT FK_CD6A7AF7DDA5A056 FOREIGN KEY (theirs_id) REFERENCES beneficiary (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
