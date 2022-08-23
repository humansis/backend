<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220704121238 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE scoring_blueprint (id INT AUTO_INCREMENT NOT NULL, created_by_user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, archived TINYINT(1) NOT NULL, content LONGBLOB NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', iso3 VARCHAR(255) NOT NULL, INDEX IDX_7B76A2CA7D182D95 (created_by_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE scoring_blueprint ADD CONSTRAINT FK_7B76A2CA7D182D95 FOREIGN KEY (created_by_user_id) REFERENCES `user` (id)');

        $this->addSql('ALTER TABLE assistance ADD scoring_blueprint_id INT DEFAULT NULL, DROP scoring_type');
        $this->addSql('ALTER TABLE assistance ADD CONSTRAINT FK_1B4F85F2B1F52A9E FOREIGN KEY (scoring_blueprint_id) REFERENCES scoring_blueprint (id)');
        $this->addSql('CREATE INDEX IDX_1B4F85F2B1F52A9E ON assistance (scoring_blueprint_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE scoring_blueprint');

        $this->addSql('ALTER TABLE assistance DROP FOREIGN KEY FK_1B4F85F2B1F52A9E');
        $this->addSql('DROP INDEX IDX_1B4F85F2B1F52A9E ON assistance');
        $this->addSql('ALTER TABLE assistance ADD scoring_type VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, DROP scoring_blueprint_id');
    }
}
