<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use ProjectBundle\DBAL\LivelihoodEnum;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220628105229 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household ADD livelihood_new ENUM(\'irregular_earnings\', \'farming_agriculture\', \'farming_livestock\', \'regular_salary_private\', \'regular_salary_public\', \'social_welfare\', \'pension\', \'home_duties\', \'own_business_trading\', \'savings\', \'remittances\', \'humanitarian_aid\', \'no_income\', \'refused_to_answer\', \'other\') DEFAULT NULL COMMENT \'(DC2Type:enum_livelihood)\'');
        $this->addSql("UPDATE household SET livelihood_new = livelihood");
        $this->addSql("UPDATE household SET livelihood_new = 'remittances' WHERE livelihood = 'remmitances'");
        $this->addSql('ALTER TABLE household DROP livelihood');
        $this->addSql("ALTER TABLE household RENAME COLUMN livelihood_new TO livelihood");
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf(true, 'Can not be downgraded');
    }
}
