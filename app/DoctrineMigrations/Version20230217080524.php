<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230217080524 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DROP TRIGGER IF EXISTS `assistance_relief_package_amount_spent_trigger_DELETE`');
        $this->addSql('DROP TRIGGER IF EXISTS `assistance_relief_package_amount_spent_trigger_UPDATE`');
        $this->addSql('DROP TRIGGER IF EXISTS `assistance_relief_package_amount_spent_trigger_INSERT`');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'Downgrade is not supported.');
    }
}
