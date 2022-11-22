<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200703094828 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard_deposit ADD distribution_beneficiary_id INT NOT NULL');
        $this->addSql('ALTER TABLE smartcard_deposit ADD CONSTRAINT FK_FD57854595AAFAA9 FOREIGN KEY (distribution_beneficiary_id) REFERENCES distribution_beneficiary (id)');
        $this->addSql('CREATE INDEX IDX_FD57854595AAFAA9 ON smartcard_deposit (distribution_beneficiary_id)');

        $this->addSql('ALTER TABLE smartcard_deposit DROP FOREIGN KEY FK_FD5785456EB6DDB5');
        $this->addSql('DROP INDEX IDX_FD5785456EB6DDB5 ON smartcard_deposit');
        $this->addSql('ALTER TABLE smartcard_deposit DROP distribution_id');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard_deposit DROP FOREIGN KEY FK_FD57854595AAFAA9');
        $this->addSql('DROP INDEX IDX_FD57854595AAFAA9 ON smartcard_deposit');
        $this->addSql('ALTER TABLE smartcard_deposit DROP distribution_beneficiary_id');
        $this->addSql('ALTER TABLE smartcard_deposit ADD distribution_beneficiary_id INT NOT NULL');
        $this->addSql('ALTER TABLE smartcard_deposit ADD CONSTRAINT FK_FD5785456EB6DDB5 FOREIGN KEY (distribution_id) REFERENCES distribution_data (id)');
        $this->addSql('CREATE INDEX IDX_FD5785456EB6DDB5 ON smartcard_deposit (distribution_id)');
    }
}
