<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220620113540 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household ADD livelihood_new ENUM(\'irregular_earnings\', \'farming_agriculture\', \'farming_livestock\', \'regular_salary_private\', \'regular_salary_public\', \'social_welfare\', \'pension\', \'home_duties\', \'own_business_trading\', \'savings\', \'remmitances\', \'humanitarian_aid\', \'no_income\', \'refused_to_answer\', \'other\') DEFAULT NULL COMMENT \'(DC2Type:enum_livelihood_new)\'');
        $this->addSql("UPDATE household SET livelihood_new = 'irregular_earnings' WHERE livelihood = 'daily_labour'");
        $this->addSql("UPDATE household SET livelihood_new = 'regular_salary_private' WHERE livelihood = 'government'");
        $this->addSql("UPDATE household SET livelihood_new = 'own_business_trading' WHERE livelihood IN ('own_business', 'trading')");
        $this->addSql("UPDATE household SET livelihood_new = 'nothing filled' WHERE livelihood  = 'textiles'");
        $this->addSql('ALTER TABLE household DROP livelihood');
        $this->addSql("ALTER TABLE household RENAME COLUMN livelihood_new TO livelihood");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household DROP livelihood_new');
    }
}
