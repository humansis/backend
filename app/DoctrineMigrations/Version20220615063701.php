<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220615063701 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DROP VIEW view_assistance_statistics;");

        $this->addSql("
            CREATE VIEW view_assistance_statistics AS
            select
                a.id as                                 assistance_id,
                count(distinct db.beneficiary_id) as    number_of_beneficiaries,
                sum(rp.amount_to_distribute) as         amount_total,
                sum(rp.amount_distributed) as           amount_distributed,
                sum(rp.amount_distributed) as           amount_used,
                sum(rp.amount_distributed) as           amount_sent,
                sum(rp.amount_distributed) as           amount_picked_up
            from assistance a
                     left join distribution_beneficiary db on a.id = db.assistance_id
                     left join assistance_relief_package rp on db.id = rp.assistance_beneficiary_id
            where rp.state != 'Canceled'
            group by a.id
            order by a.id;
        ");

    }

    public function down(Schema $schema) : void
    {
        $this->abortIf(true, 'Cannot be downgraded');
    }
}
