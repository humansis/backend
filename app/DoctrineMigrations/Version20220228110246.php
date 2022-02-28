<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use NewApiBundle\DBAL\NationalIdTypeEnum;
use NewApiBundle\Enum\EnumValueNoFoundException;
use NewApiBundle\Enum\NationalIdType;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220228110246 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX duplicity_check_idx ON national_id');
        $this->addSql('ALTER TABLE national_id CHANGE id_type old_type VARCHAR(45) CHARACTER SET utf8 DEFAULT \'\' COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE national_id ADD id_type ENUM(\'National ID\', \'Passport\', \'Family Registration\', \'Family Book\', \'Birth Certificate\', \'Driver’s License\', \'Camp ID\', \'Social Service Card\', \'Other\', \'None\') NOT NULL COMMENT \'(DC2Type:enum_national_id_type)\' AFTER old_type');
        $this->addSql('create index duplicity_check_idx on national_id (id_type, id_number);');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX duplicity_check_idx ON national_id');
        $this->addSql('ALTER TABLE national_id CHANGE id_type id_type VARCHAR(45) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('create index duplicity_check_idx on national_id (id_type, id_number);');
    }
}
