<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220915075918 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE national_id CHANGE `id_type` `id_type` ENUM(\'National ID\', \'Tax Number\', \'Passport\', \'Family Registration\', \'Birth Certificate\', \'Driver’s License\', \'Camp ID\', \'Social Service Card\', \'Other\', \'None\', \'Civil registration record\') NOT NULL COMMENT \'(DC2Type:enum_national_id_type)\' AFTER old_type');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE national_id CHANGE `id_type` `id_type` ENUM(\'National ID\', \'Tax Number\', \'Passport\', \'Family Registration\', \'Birth Certificate\', \'Driver’s License\', \'Camp ID\', \'Social Service Card\', \'Other\', \'None\') NOT NULL COMMENT \'(DC2Type:enum_national_id_type)\' AFTER old_type');
    }
}
