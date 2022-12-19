<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221010095908 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DROP VIEW view_assistance_statistics');

        $this->addSql("
            CREATE VIEW view_assistance_statistics AS
            select a.id                                                        as assistance_id,
                   count(distinct db.beneficiary_id)                           as number_of_beneficiaries,
                   sum(IF(rp.state != 'Canceled', rp.amount_to_distribute, 0)) as amount_total,
                   sum(rp.amount_distributed)                                  as amount_distributed,
                   sum(IF(rp.state = 'Canceled', 1, 0))                        as beneficiaries_deleted,
                   sum(IF(rp.state = 'Distributed', 1, 0))                     as beneficiaries_reached
            from assistance a
                     left join distribution_beneficiary db on a.id = db.assistance_id
                     left join assistance_relief_package rp on db.id = rp.assistance_beneficiary_id
            group by a.id
            order by a.id
        ");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'No downgrade');
    }
}
