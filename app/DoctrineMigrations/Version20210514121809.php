<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210514121809 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            CREATE TABLE import (
                id INT AUTO_INCREMENT NOT NULL,
                project_id INT DEFAULT NULL,
                created_by_id INT DEFAULT NULL,
                title VARCHAR(255) NOT NULL,
                notes VARCHAR(255) DEFAULT NULL,
                state ENUM(\'New\', \'Integrity Checking\', \'Integrity Check Correct\', \'Integrity Check Failed\', \'Identity Checking\', \'Identity Check Correct\', \'Identity Check Failed\', \'Similarity Checking\', \'Similarity Check Correct\', \'Similarity Check Failed\', \'Finished\', \'Canceled\') NOT NULL COMMENT \'(DC2Type:enum_import_state)\', created_at DATETIME NOT NULL,
                INDEX IDX_9D4ECE1D166D1F9C (project_id),
                INDEX IDX_9D4ECE1DB03A8386 (created_by_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_9D4ECE1D166D1F9C FOREIGN KEY (project_id)
                    REFERENCES project (id),
                CONSTRAINT FK_9D4ECE1DB03A8386 FOREIGN KEY (created_by_id)
                    REFERENCES `user` (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('
            CREATE TABLE import_file (
                id INT AUTO_INCREMENT NOT NULL,
                import_id INT DEFAULT NULL,
                user_id INT DEFAULT NULL,
                filename VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_61B3D890A76ED395 (user_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_61B3D890B6A263D9 FOREIGN KEY (import_id)
                    REFERENCES import (id),
                CONSTRAINT FK_61B3D890A76ED395 FOREIGN KEY (user_id)
                    REFERENCES `user` (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('
            CREATE TABLE import_queue (
                id INT AUTO_INCREMENT NOT NULL,
                import_id INT DEFAULT NULL,
                file_id INT DEFAULT NULL,
                content JSON NOT NULL,
                state ENUM(\'New\', \'Valid\', \'Invalid\', \'Suspicious\', \'To Create\', \'To Update\', \'To Link\', \'To Ignore\') NOT NULL COMMENT \'(DC2Type:enum_import_queue_state)\',
                message LONGTEXT DEFAULT NULL,
                INDEX IDX_92A8D0ADB6A263D9 (import_id),
                INDEX IDX_92A8D0AD93CB796C (file_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_92A8D0ADB6A263D9 FOREIGN KEY (import_id)
                    REFERENCES import (id),
                CONSTRAINT FK_92A8D0AD93CB796C FOREIGN KEY (file_id)
                    REFERENCES import_file (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('
            CREATE TABLE import_beneficiary (
                id INT AUTO_INCREMENT NOT NULL,
                import_id INT DEFAULT NULL,
                beneficiary_id INT DEFAULT NULL,
                created_by_id INT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_FEC38F8AB6A263D9 (import_id),
                INDEX IDX_FEC38F8AECCAAFA0 (beneficiary_id),
                INDEX IDX_FEC38F8AB03A8386 (created_by_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_FEC38F8AB6A263D9 FOREIGN KEY (import_id)
                    REFERENCES import (id),
                CONSTRAINT FK_FEC38F8AECCAAFA0 FOREIGN KEY (beneficiary_id)
                    REFERENCES beneficiary (id),
                CONSTRAINT FK_FEC38F8AB03A8386 FOREIGN KEY (created_by_id)
                    REFERENCES `user` (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('
            CREATE TABLE import_queue_duplicity (
                id INT AUTO_INCREMENT NOT NULL,
                ours_id INT DEFAULT NULL,
                theirs_id INT DEFAULT NULL,
                decide_by_id INT DEFAULT NULL,
                state ENUM(\'Duplicity Candidate\', \'Duplicity Keep Ours\', \'Duplicity Keep Theirs\', \'No Duplicity\') NOT NULL COMMENT \'(DC2Type:enum_import_duplicity_state)\',
                decide_at DATETIME NOT NULL,
                INDEX IDX_C977685D8E5DFFF (ours_id),
                INDEX IDX_C977685DDA5A056 (theirs_id),
                INDEX IDX_C977685A72083D6 (decide_by_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_C977685D8E5DFFF FOREIGN KEY (ours_id)
                    REFERENCES import_queue (id),
                CONSTRAINT FK_C977685DDA5A056 FOREIGN KEY (theirs_id)
                    REFERENCES import_queue (id),
                CONSTRAINT FK_C977685A72083D6 FOREIGN KEY (decide_by_id)
                    REFERENCES `user` (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('
            CREATE TABLE import_beneficiary_duplicity (
                id INT AUTO_INCREMENT NOT NULL,
                ours_id INT DEFAULT NULL,
                theirs_id INT DEFAULT NULL,
                decide_by_id INT DEFAULT NULL,
                state ENUM(\'Duplicity Candidate\', \'Duplicity Keep Ours\', \'Duplicity Keep Theirs\', \'No Duplicity\') NOT NULL COMMENT \'(DC2Type:enum_import_duplicity_state)\',
                decide_at DATETIME NOT NULL,
                INDEX IDX_CD6A7AF7D8E5DFFF (ours_id),
                INDEX IDX_CD6A7AF7DDA5A056 (theirs_id),
                INDEX IDX_CD6A7AF7A72083D6 (decide_by_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_CD6A7AF7D8E5DFFF FOREIGN KEY (ours_id)
                    REFERENCES import_queue (id),
                CONSTRAINT FK_CD6A7AF7DDA5A056 FOREIGN KEY (theirs_id)
                    REFERENCES beneficiary (id),
                CONSTRAINT FK_CD6A7AF7A72083D6 FOREIGN KEY (decide_by_id)
                    REFERENCES `user` (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE import_queue DROP FOREIGN KEY FK_92A8D0AD93CB796C');
        $this->addSql('ALTER TABLE import_queue_duplicity DROP FOREIGN KEY FK_C977685D8E5DFFF');
        $this->addSql('ALTER TABLE import_queue_duplicity DROP FOREIGN KEY FK_C977685DDA5A056');
        $this->addSql('ALTER TABLE import_beneficiary_duplicity DROP FOREIGN KEY FK_CD6A7AF7D8E5DFFF');
        $this->addSql('ALTER TABLE import_queue DROP FOREIGN KEY FK_92A8D0ADB6A263D9');
        $this->addSql('ALTER TABLE import_beneficiary DROP FOREIGN KEY FK_FEC38F8AB6A263D9');
        $this->addSql('DROP TABLE import_queue_duplicity');
        $this->addSql('DROP TABLE import_file');
        $this->addSql('DROP TABLE import_beneficiary_duplicity');
        $this->addSql('DROP TABLE import_queue');
        $this->addSql('DROP TABLE import');
        $this->addSql('DROP TABLE import_beneficiary');
    }
}
