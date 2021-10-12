<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211012102508 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard_deposit DROP FOREIGN KEY FK_FD57854595AAFAA9');
        $this->addSql('ALTER TABLE smartcard_deposit DROP FOREIGN KEY FK_FD578545EB8724B4');
        $this->addSql('DROP INDEX IDX_FD57854595AAFAA9 ON smartcard_deposit');
        $this->addSql('DROP INDEX IDX_FD578545EB8724B4 ON smartcard_deposit');
        $this->addSql('ALTER TABLE smartcard_deposit ADD assistance_beneficiary_commodity_id INT DEFAULT NULL, ADD distributed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE smartcard_deposit RENAME COLUMN depositor_id TO distributed_by_id');
        $this->addSql('ALTER TABLE smartcard_deposit ADD CONSTRAINT FK_FD57854565F14916 FOREIGN KEY (distributed_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE smartcard_deposit ADD CONSTRAINT FK_FD5785451E8DF071 FOREIGN KEY (assistance_beneficiary_commodity_id) REFERENCES assistance_beneficiary_commodity (id)');
        $this->addSql('CREATE INDEX IDX_FD57854565F14916 ON smartcard_deposit (distributed_by_id)');
        $this->addSql('CREATE INDEX IDX_FD5785451E8DF071 ON smartcard_deposit (assistance_beneficiary_commodity_id)');

        // all validated SC assistances must have one ABC
        $this->addSql("INSERT INTO assistance_beneficiary_commodity (
                                assistance_beneficiary_id,
                                state,
                                modality_type,
                                amount_to_distribute,
                                amount_distributed,
                                unit,
                                created_at
                            )
                            SELECT
                                db.id,
                                IF(SUM(sd.value)>=c.value, 'Distributed', 'To distribute'),
                                'Smartcard',
                                c.value,
                                IF(SUM(sd.value) IS NOT NULL, SUM(sd.value), 0),
                                c.unit,
                                IF(MIN(sd.used_at) IS NOT NULL, MIN(sd.used_at), NOW())
                            FROM
                                distribution_beneficiary db
                                INNER JOIN assistance as a on a.id = db.assistance_id
                                INNER JOIN commodity c on a.id = c.assistance_id
                                INNER JOIN modality_type mt on c.modality_type_id = mt.id
                                LEFT OUTER JOIN smartcard_deposit sd on db.id = sd.distribution_beneficiary_id
                            WHERE
                                mt.name='Smartcard'
                            GROUP BY db.id, c.id
                            ;
        ");

        $this->addSql('ALTER TABLE smartcard_deposit DROP distribution_beneficiary_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(true, 'Cant be downgraded.');
    }
}
