<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190204151850 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            '
            CREATE TABLE donor (
                id INT AUTO_INCREMENT NOT NULL,
                fullname VARCHAR(255) NOT NULL,
                shortname VARCHAR(255) DEFAULT NULL,
                dateAdded DATETIME NOT NULL,
                notes VARCHAR(255) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE sector (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE profile (
                id INT AUTO_INCREMENT NOT NULL,
                photo VARCHAR(255) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE project (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                startDate DATE NOT NULL,
                endDate DATE NOT NULL,
                value DOUBLE PRECISION DEFAULT NULL,
                notes LONGTEXT DEFAULT NULL,
                iso3 LONGTEXT NOT NULL,
                archived TINYINT(1) DEFAULT \'0\' NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE project_donor (
                project_id INT NOT NULL,
                donor_id INT NOT NULL,
                INDEX IDX_C4A74909166D1F9C (project_id),
                INDEX IDX_C4A749093DD7B7A7 (donor_id),
                PRIMARY KEY(project_id, donor_id),
                CONSTRAINT FK_C4A74909166D1F9C FOREIGN KEY (project_id)
                    REFERENCES project (id)
                    ON DELETE CASCADE,
                CONSTRAINT FK_C4A749093DD7B7A7 FOREIGN KEY (donor_id)
                    REFERENCES donor (id)
                    ON DELETE CASCADE
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE project_sector (project_id INT NOT NULL,
                sector_id INT NOT NULL,
                INDEX IDX_5C0732A2166D1F9C (project_id),
                INDEX IDX_5C0732A2DE95C867 (sector_id),
                PRIMARY KEY(project_id, sector_id),
                CONSTRAINT FK_5C0732A2166D1F9C FOREIGN KEY (project_id)
                    REFERENCES project (id)
                    ON DELETE CASCADE,
                CONSTRAINT FK_5C0732A2DE95C867 FOREIGN KEY (sector_id)
                    REFERENCES sector (id)
                    ON DELETE CASCADE
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE location (
                id INT AUTO_INCREMENT NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE adm1 (
                id INT AUTO_INCREMENT NOT NULL,
                location_id INT DEFAULT NULL,
                name VARCHAR(255) NOT NULL,
                countryISO3 VARCHAR(3) NOT NULL,
                code VARCHAR(255) DEFAULT NULL,
                UNIQUE INDEX UNIQ_6C8D395664D218E (location_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_6C8D395664D218E FOREIGN KEY (location_id)
                    REFERENCES location (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE adm2 (
                id INT AUTO_INCREMENT NOT NULL,
                adm1_id INT DEFAULT NULL,
                location_id INT DEFAULT NULL,
                name VARCHAR(255) NOT NULL,
                code VARCHAR(255) DEFAULT NULL,
                INDEX IDX_F58468EC93FDE579 (adm1_id),
                UNIQUE INDEX UNIQ_F58468EC64D218E (location_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_F58468EC93FDE579 FOREIGN KEY (adm1_id)
                    REFERENCES adm1 (id),
                CONSTRAINT FK_F58468EC64D218E FOREIGN KEY (location_id)
                    REFERENCES location (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE adm3 (
                id INT AUTO_INCREMENT NOT NULL,
                adm2_id INT DEFAULT NULL,
                location_id INT DEFAULT NULL,
                name VARCHAR(255) NOT NULL,
                code VARCHAR(255) DEFAULT NULL,
                INDEX IDX_8283587A81484A97 (adm2_id),
                UNIQUE INDEX UNIQ_8283587A64D218E (location_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_8283587A81484A97 FOREIGN KEY (adm2_id)
                    REFERENCES adm2 (id),
                CONSTRAINT FK_8283587A64D218E FOREIGN KEY (location_id)
                    REFERENCES location (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE adm4 (
                id INT AUTO_INCREMENT NOT NULL,
                adm3_id INT DEFAULT NULL,
                location_id INT DEFAULT NULL,
                name VARCHAR(255) NOT NULL,
                code VARCHAR(255) DEFAULT NULL,
                INDEX IDX_1CE7CDD939F42DF2 (adm3_id),
                UNIQUE INDEX UNIQ_1CE7CDD964D218E (location_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_1CE7CDD939F42DF2 FOREIGN KEY (adm3_id)
                    REFERENCES adm3 (id),
                CONSTRAINT FK_1CE7CDD964D218E FOREIGN KEY (location_id)
                    REFERENCES location (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE household (
                id INT AUTO_INCREMENT NOT NULL,
                location_id INT DEFAULT NULL,
                address_street VARCHAR(255) DEFAULT NULL,
                address_number VARCHAR(255) DEFAULT NULL,
                address_postcode VARCHAR(255) DEFAULT NULL,
                livelihood INT DEFAULT NULL,
                notes VARCHAR(255) DEFAULT NULL,
                latitude VARCHAR(45) DEFAULT NULL,
                longitude VARCHAR(45) DEFAULT NULL,
                archived TINYINT(1) DEFAULT \'0\' NOT NULL,
                INDEX IDX_54C32FC064D218E (location_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_54C32FC064D218E FOREIGN KEY (location_id)
                    REFERENCES location (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE household_project (
                household_id INT NOT NULL,
                project_id INT NOT NULL,
                INDEX IDX_42473AC0E79FF843 (household_id),
                INDEX IDX_42473AC0166D1F9C (project_id),
                PRIMARY KEY(household_id, project_id),
                CONSTRAINT FK_42473AC0E79FF843 FOREIGN KEY (household_id)
                    REFERENCES household (id)
                    ON DELETE CASCADE,
                CONSTRAINT FK_42473AC0166D1F9C FOREIGN KEY (project_id)
                    REFERENCES project (id)
                    ON DELETE CASCADE
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL,
                username VARCHAR(180) NOT NULL,
                username_canonical VARCHAR(180) NOT NULL,
                email VARCHAR(180) NOT NULL,
                email_canonical VARCHAR(180) NOT NULL,
                enabled TINYINT(1) NOT NULL,
                salt VARCHAR(255) DEFAULT NULL,
                password VARCHAR(255) NOT NULL,
                last_login DATETIME DEFAULT NULL,
                confirmation_token VARCHAR(180) DEFAULT NULL,
                password_requested_at DATETIME DEFAULT NULL,
                roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\',
                language VARCHAR(255) DEFAULT NULL,
                UNIQUE INDEX UNIQ_8D93D64992FC23A8 (username_canonical),
                UNIQUE INDEX UNIQ_8D93D649A0D96FBF (email_canonical),
                UNIQUE INDEX UNIQ_8D93D649C05FB297 (confirmation_token),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE user_country (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT DEFAULT NULL,
                rights VARCHAR(255) NOT NULL,
                iso3 VARCHAR(3) NOT NULL,
                INDEX IDX_B7ED76CA76ED395 (user_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_B7ED76CA76ED395 FOREIGN KEY (user_id)
                    REFERENCES `user` (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE user_project (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT DEFAULT NULL,
                project_id INT DEFAULT NULL,
                rights VARCHAR(255) NOT NULL,
                INDEX IDX_77BECEE4A76ED395 (user_id),
                INDEX IDX_77BECEE4166D1F9C (project_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_77BECEE4A76ED395 FOREIGN KEY (user_id)
                    REFERENCES `user` (id),
                CONSTRAINT FK_77BECEE4166D1F9C FOREIGN KEY (project_id)
                    REFERENCES project (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE vulnerability_criterion (
                id INT AUTO_INCREMENT NOT NULL,
                field_string VARCHAR(255) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE beneficiary (
                id INT AUTO_INCREMENT NOT NULL,
                profile_id INT DEFAULT NULL,
                household_id INT DEFAULT NULL,
                givenName VARCHAR(255) DEFAULT NULL,
                familyName VARCHAR(255) DEFAULT NULL,
                gender SMALLINT NOT NULL,
                status TINYINT(1) NOT NULL,
                dateOfBirth DATE NOT NULL,
                updated_on DATETIME DEFAULT NULL,
                UNIQUE INDEX UNIQ_7ABF446ACCFA12B8 (profile_id),
                INDEX IDX_7ABF446AE79FF843 (household_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_7ABF446ACCFA12B8 FOREIGN KEY (profile_id)
                    REFERENCES profile (id),
                CONSTRAINT FK_7ABF446AE79FF843 FOREIGN KEY (household_id)
                    REFERENCES household (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE beneficiary_vulnerability_criterion (
                beneficiary_id INT NOT NULL,
                vulnerability_criterion_id INT NOT NULL,
                INDEX IDX_566B5C7ECCAAFA0 (beneficiary_id),
                INDEX IDX_566B5C77255F7BA (vulnerability_criterion_id),
                PRIMARY KEY(beneficiary_id, vulnerability_criterion_id),
                CONSTRAINT FK_566B5C7ECCAAFA0 FOREIGN KEY (beneficiary_id)
                    REFERENCES beneficiary (id)
                    ON DELETE CASCADE,
                CONSTRAINT FK_566B5C77255F7BA FOREIGN KEY (vulnerability_criterion_id)
                    REFERENCES vulnerability_criterion (id)
                    ON DELETE CASCADE
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE country_specific (
                id INT AUTO_INCREMENT NOT NULL,
                field_string VARCHAR(45) NOT NULL,
                type VARCHAR(45) NOT NULL,
                country_iso3 VARCHAR(45) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE phone (
                id INT AUTO_INCREMENT NOT NULL,
                beneficiary_id INT DEFAULT NULL,
                number VARCHAR(45) NOT NULL,
                type VARCHAR(45) NOT NULL,
                prefix VARCHAR(45) NOT NULL,
                proxy TINYINT(1) NOT NULL,
                INDEX IDX_444F97DDECCAAFA0 (beneficiary_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_444F97DDECCAAFA0 FOREIGN KEY (beneficiary_id)
                    REFERENCES beneficiary (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE national_id (
                id INT AUTO_INCREMENT NOT NULL,
                beneficiary_id INT DEFAULT NULL,
                id_number VARCHAR(45) NOT NULL,
                id_type VARCHAR(45) NOT NULL,
                INDEX IDX_36491297ECCAAFA0 (beneficiary_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_36491297ECCAAFA0 FOREIGN KEY (beneficiary_id)
                    REFERENCES beneficiary (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE country_specific_answer (
                id INT AUTO_INCREMENT NOT NULL,
                country_specific_id INT DEFAULT NULL,
                household_id INT DEFAULT NULL,
                answer VARCHAR(255) DEFAULT NULL,
                INDEX IDX_4680BB30433BFD7C (country_specific_id),
                INDEX IDX_4680BB30E79FF843 (household_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_4680BB30433BFD7C FOREIGN KEY (country_specific_id)
                    REFERENCES country_specific (id),
                CONSTRAINT FK_4680BB30E79FF843 FOREIGN KEY (household_id)
                    REFERENCES household (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE distribution_data (
                id INT AUTO_INCREMENT NOT NULL,
                location_id INT DEFAULT NULL,
                project_id INT DEFAULT NULL,
                name VARCHAR(45) NOT NULL,
                UpdatedOn DATETIME NOT NULL,
                date_distribution DATE NOT NULL,
                archived TINYINT(1) DEFAULT \'0\' NOT NULL,
                validated TINYINT(1) DEFAULT \'0\' NOT NULL,
                type_distribution INT NOT NULL,
                INDEX IDX_A54E7FD764D218E (location_id),
                INDEX IDX_A54E7FD7166D1F9C (project_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_A54E7FD764D218E FOREIGN KEY (location_id)
                    REFERENCES location (id),
                CONSTRAINT FK_A54E7FD7166D1F9C FOREIGN KEY (project_id)
                    REFERENCES project (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE distribution_beneficiary (
                id INT AUTO_INCREMENT NOT NULL,
                distribution_data_id INT DEFAULT NULL,
                beneficiary_id INT DEFAULT NULL,
                INDEX IDX_EA141F30D744EF8E (distribution_data_id),
                INDEX IDX_EA141F30ECCAAFA0 (beneficiary_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_EA141F30D744EF8E FOREIGN KEY (distribution_data_id)
                    REFERENCES distribution_data (id),
                CONSTRAINT FK_EA141F30ECCAAFA0 FOREIGN KEY (beneficiary_id)
                    REFERENCES beneficiary (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE selection_criteria (
                id INT AUTO_INCREMENT NOT NULL,
                distribution_data_id INT DEFAULT NULL,
                table_string VARCHAR(255) NOT NULL,
                kind_beneficiary VARCHAR(255) DEFAULT NULL,
                field_string VARCHAR(255) DEFAULT NULL,
                field_id INT DEFAULT NULL,
                condition_string VARCHAR(255) DEFAULT NULL,
                value_string VARCHAR(255) DEFAULT NULL,
                weight INT NOT NULL,
                INDEX IDX_61BAEEC9D744EF8E (distribution_data_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_61BAEEC9D744EF8E FOREIGN KEY (distribution_data_id)
                    REFERENCES distribution_data (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE modality (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                UNIQUE INDEX UNIQ_307988C05E237E06 (name),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE modality_type (
                id INT AUTO_INCREMENT NOT NULL,
                modality_id INT DEFAULT NULL,
                name VARCHAR(255) NOT NULL,
                INDEX IDX_946534112D6D889B (modality_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_946534112D6D889B FOREIGN KEY (modality_id)
                    REFERENCES modality (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE commodity (
                id INT AUTO_INCREMENT NOT NULL,
                modality_type_id INT DEFAULT NULL,
                distribution_data_id INT DEFAULT NULL,
                unit VARCHAR(45) NOT NULL,
                value DOUBLE PRECISION NOT NULL,
                INDEX IDX_5E8D2F74FD576AC3 (modality_type_id),
                INDEX IDX_5E8D2F74D744EF8E (distribution_data_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_5E8D2F74FD576AC3 FOREIGN KEY (modality_type_id)
                    REFERENCES modality_type (id),
                CONSTRAINT FK_5E8D2F74D744EF8E FOREIGN KEY (distribution_data_id)
                    REFERENCES distribution_data (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE transaction (
                id INT AUTO_INCREMENT NOT NULL,
                distribution_beneficiary_id INT DEFAULT NULL,
                sent_by_id INT DEFAULT NULL,
                transaction_id VARCHAR(45) NOT NULL,
                amount_sent VARCHAR(255) NOT NULL,
                date_sent DATETIME NOT NULL,
                transaction_status SMALLINT NOT NULL,
                message VARCHAR(255) DEFAULT NULL,
                money_received TINYINT(1) DEFAULT NULL,
                pickup_date DATETIME DEFAULT NULL,
                updated_on DATETIME DEFAULT NULL,
                INDEX IDX_723705D195AAFAA9 (distribution_beneficiary_id),
                INDEX IDX_723705D1A45BB98C (sent_by_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_723705D195AAFAA9 FOREIGN KEY (distribution_beneficiary_id)
                    REFERENCES distribution_beneficiary (id),
                CONSTRAINT FK_723705D1A45BB98C FOREIGN KEY (sent_by_id) REFERENCES `user` (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE financial_provider (
                id INT AUTO_INCREMENT NOT NULL,
                username VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                country VARCHAR(255) NOT NULL,
                UNIQUE INDEX UNIQ_3FF138985373C966 (country),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE logs (
                id INT AUTO_INCREMENT NOT NULL,
                url VARCHAR(255) NOT NULL,
                idUser INT NOT NULL,
                mailUser VARCHAR(255) NOT NULL,
                method VARCHAR(255) NOT NULL,
                date DATETIME NOT NULL,
                httpStatus INT NOT NULL,
                controller VARCHAR(255) NOT NULL,
                request LONGTEXT NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE reporting_indicator (
                id INT AUTO_INCREMENT NOT NULL,
                reference VARCHAR(255) NOT NULL,
                code VARCHAR(255) NOT NULL,
                filters LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\',
                graph VARCHAR(255) DEFAULT NULL,
                UNIQUE INDEX UNIQ_158D0C7177153098 (code),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE reporting_value (
                id INT AUTO_INCREMENT NOT NULL,
                value VARCHAR(255) NOT NULL,
                unity VARCHAR(255) NOT NULL,
                creationDate DATETIME NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE reporting_project (
                id INT AUTO_INCREMENT NOT NULL,
                project_id INT DEFAULT NULL,
                indicator_id INT DEFAULT NULL,
                value_id INT NOT NULL,
                INDEX IDX_F9E2F346166D1F9C (project_id),
                INDEX IDX_F9E2F3464402854A (indicator_id),
                INDEX IDX_F9E2F346F920BBA2 (value_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_F9E2F346166D1F9C FOREIGN KEY (project_id)
                    REFERENCES project (id),
                CONSTRAINT FK_F9E2F3464402854A FOREIGN KEY (indicator_id)
                    REFERENCES reporting_indicator (id),
                CONSTRAINT FK_F9E2F346F920BBA2 FOREIGN KEY (value_id)
                    REFERENCES reporting_value (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE reporting_distribution (
                id INT AUTO_INCREMENT NOT NULL,
                distribution_id INT DEFAULT NULL,
                indicator_id INT DEFAULT NULL,
                value_id INT NOT NULL,
                INDEX IDX_EC84C5186EB6DDB5 (distribution_id),
                INDEX IDX_EC84C5184402854A (indicator_id),
                INDEX IDX_EC84C518F920BBA2 (value_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_EC84C5186EB6DDB5 FOREIGN KEY (distribution_id)
                    REFERENCES distribution_data (id),
                CONSTRAINT FK_EC84C5184402854A FOREIGN KEY (indicator_id)
                    REFERENCES reporting_indicator (id),
                CONSTRAINT FK_EC84C518F920BBA2 FOREIGN KEY (value_id)
                    REFERENCES reporting_value (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE reporting_country (
                id INT AUTO_INCREMENT NOT NULL,
                indicator_id INT DEFAULT NULL,
                value_id INT NOT NULL,
                country VARCHAR(255) NOT NULL,
                INDEX IDX_8522EACE4402854A (indicator_id),
                INDEX IDX_8522EACEF920BBA2 (value_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_8522EACE4402854A FOREIGN KEY (indicator_id)
                    REFERENCES reporting_indicator (id),
                CONSTRAINT FK_8522EACEF920BBA2 FOREIGN KEY (value_id)
                    REFERENCES reporting_value (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE product (
                id INT AUTO_INCREMENT NOT NULL,
                description VARCHAR(255) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE booklet (
                id INT AUTO_INCREMENT NOT NULL,
                distribution_beneficiary_id INT DEFAULT NULL,
                code VARCHAR(255) NOT NULL,
                number_vouchers INT NOT NULL,
                currency VARCHAR(255) NOT NULL,
                status INT DEFAULT NULL,
                password VARCHAR(255) DEFAULT NULL,
                UNIQUE INDEX UNIQ_818DB72077153098 (code),
                INDEX IDX_818DB72095AAFAA9 (distribution_beneficiary_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_818DB72095AAFAA9 FOREIGN KEY (distribution_beneficiary_id)
                    REFERENCES distribution_beneficiary (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE booklet_product (
                booklet_id INT NOT NULL,
                product_id INT NOT NULL,
                INDEX IDX_950E688C668144B3 (booklet_id),
                INDEX IDX_950E688C4584665A (product_id),
                PRIMARY KEY(booklet_id,
                product_id),
                CONSTRAINT FK_950E688C668144B3 FOREIGN KEY (booklet_id)
                    REFERENCES booklet (id) ON DELETE CASCADE,
                CONSTRAINT FK_950E688C4584665A FOREIGN KEY (product_id)
                    REFERENCES product (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE vendor (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                shop VARCHAR(255) NOT NULL,
                address VARCHAR(255) NOT NULL,
                username VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                archived TINYINT(1) NOT NULL,
                UNIQUE INDEX UNIQ_F52233F6F85E0677 (username),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE voucher (
                id INT AUTO_INCREMENT NOT NULL,
                booklet_id INT NOT NULL,
                vendor_id INT DEFAULT NULL,
                used TINYINT(1) NOT NULL,
                code VARCHAR(255) NOT NULL,
                individual_value INT NOT NULL,
                UNIQUE INDEX UNIQ_1392A5D877153098 (code),
                INDEX IDX_1392A5D8668144B3 (booklet_id),
                INDEX IDX_1392A5D8F603EE73 (vendor_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_1392A5D8668144B3 FOREIGN KEY (booklet_id)
                    REFERENCES booklet (id),
                CONSTRAINT FK_1392A5D8F603EE73 FOREIGN KEY (vendor_id)
                    REFERENCES vendor (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            '
            CREATE TABLE voucher_product (
                voucher_id INT NOT NULL,
                product_id INT NOT NULL,
                INDEX IDX_10872EAA28AA1B6F (voucher_id),
                INDEX IDX_10872EAA4584665A (product_id),
                PRIMARY KEY(voucher_id,
                product_id),
                CONSTRAINT FK_10872EAA28AA1B6F FOREIGN KEY (voucher_id)
                    REFERENCES voucher (id)
                    ON DELETE CASCADE,
                CONSTRAINT FK_10872EAA4584665A FOREIGN KEY (product_id)
                    REFERENCES product (id)
                    ON DELETE CASCADE
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_project DROP FOREIGN KEY FK_77BECEE4A76ED395');
        $this->addSql('ALTER TABLE user_country DROP FOREIGN KEY FK_B7ED76CA76ED395');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1A45BB98C');
        $this->addSql('ALTER TABLE project_donor DROP FOREIGN KEY FK_C4A749093DD7B7A7');
        $this->addSql('ALTER TABLE project_sector DROP FOREIGN KEY FK_5C0732A2DE95C867');
        $this->addSql('ALTER TABLE user_project DROP FOREIGN KEY FK_77BECEE4166D1F9C');
        $this->addSql('ALTER TABLE project_donor DROP FOREIGN KEY FK_C4A74909166D1F9C');
        $this->addSql('ALTER TABLE project_sector DROP FOREIGN KEY FK_5C0732A2166D1F9C');
        $this->addSql('ALTER TABLE household_project DROP FOREIGN KEY FK_42473AC0166D1F9C');
        $this->addSql('ALTER TABLE distribution_data DROP FOREIGN KEY FK_A54E7FD7166D1F9C');
        $this->addSql('ALTER TABLE reporting_project DROP FOREIGN KEY FK_F9E2F346166D1F9C');
        $this->addSql('ALTER TABLE beneficiary_vulnerability_criterion DROP FOREIGN KEY FK_566B5C77255F7BA');
        $this->addSql('ALTER TABLE beneficiary_vulnerability_criterion DROP FOREIGN KEY FK_566B5C7ECCAAFA0');
        $this->addSql('ALTER TABLE phone DROP FOREIGN KEY FK_444F97DDECCAAFA0');
        $this->addSql('ALTER TABLE national_id DROP FOREIGN KEY FK_36491297ECCAAFA0');
        $this->addSql('ALTER TABLE distribution_beneficiary DROP FOREIGN KEY FK_EA141F30ECCAAFA0');
        $this->addSql('ALTER TABLE country_specific_answer DROP FOREIGN KEY FK_4680BB30433BFD7C');
        $this->addSql('ALTER TABLE beneficiary DROP FOREIGN KEY FK_7ABF446ACCFA12B8');
        $this->addSql('ALTER TABLE beneficiary DROP FOREIGN KEY FK_7ABF446AE79FF843');
        $this->addSql('ALTER TABLE household_project DROP FOREIGN KEY FK_42473AC0E79FF843');
        $this->addSql('ALTER TABLE country_specific_answer DROP FOREIGN KEY FK_4680BB30E79FF843');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D195AAFAA9');
        $this->addSql('ALTER TABLE booklet DROP FOREIGN KEY FK_818DB72095AAFAA9');
        $this->addSql('ALTER TABLE modality_type DROP FOREIGN KEY FK_946534112D6D889B');
        $this->addSql('ALTER TABLE commodity DROP FOREIGN KEY FK_5E8D2F74FD576AC3');
        $this->addSql('ALTER TABLE selection_criteria DROP FOREIGN KEY FK_61BAEEC9D744EF8E');
        $this->addSql('ALTER TABLE distribution_beneficiary DROP FOREIGN KEY FK_EA141F30D744EF8E');
        $this->addSql('ALTER TABLE commodity DROP FOREIGN KEY FK_5E8D2F74D744EF8E');
        $this->addSql('ALTER TABLE reporting_distribution DROP FOREIGN KEY FK_EC84C5186EB6DDB5');
        $this->addSql('ALTER TABLE adm2 DROP FOREIGN KEY FK_F58468EC93FDE579');
        $this->addSql('ALTER TABLE adm3 DROP FOREIGN KEY FK_8283587A81484A97');
        $this->addSql('ALTER TABLE adm4 DROP FOREIGN KEY FK_1CE7CDD939F42DF2');
        $this->addSql('ALTER TABLE household DROP FOREIGN KEY FK_54C32FC064D218E');
        $this->addSql('ALTER TABLE distribution_data DROP FOREIGN KEY FK_A54E7FD764D218E');
        $this->addSql('ALTER TABLE adm1 DROP FOREIGN KEY FK_6C8D395664D218E');
        $this->addSql('ALTER TABLE adm2 DROP FOREIGN KEY FK_F58468EC64D218E');
        $this->addSql('ALTER TABLE adm3 DROP FOREIGN KEY FK_8283587A64D218E');
        $this->addSql('ALTER TABLE adm4 DROP FOREIGN KEY FK_1CE7CDD964D218E');
        $this->addSql('ALTER TABLE reporting_project DROP FOREIGN KEY FK_F9E2F3464402854A');
        $this->addSql('ALTER TABLE reporting_distribution DROP FOREIGN KEY FK_EC84C5184402854A');
        $this->addSql('ALTER TABLE reporting_country DROP FOREIGN KEY FK_8522EACE4402854A');
        $this->addSql('ALTER TABLE reporting_project DROP FOREIGN KEY FK_F9E2F346F920BBA2');
        $this->addSql('ALTER TABLE reporting_distribution DROP FOREIGN KEY FK_EC84C518F920BBA2');
        $this->addSql('ALTER TABLE reporting_country DROP FOREIGN KEY FK_8522EACEF920BBA2');
        $this->addSql('ALTER TABLE booklet_product DROP FOREIGN KEY FK_950E688C4584665A');
        $this->addSql('ALTER TABLE voucher_product DROP FOREIGN KEY FK_10872EAA4584665A');
        $this->addSql('ALTER TABLE booklet_product DROP FOREIGN KEY FK_950E688C668144B3');
        $this->addSql('ALTER TABLE voucher DROP FOREIGN KEY FK_1392A5D8668144B3');
        $this->addSql('ALTER TABLE voucher DROP FOREIGN KEY FK_1392A5D8F603EE73');
        $this->addSql('ALTER TABLE voucher_product DROP FOREIGN KEY FK_10872EAA28AA1B6F');
        $this->addSql('DROP TABLE user_project');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE user_country');
        $this->addSql('DROP TABLE donor');
        $this->addSql('DROP TABLE sector');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE project_donor');
        $this->addSql('DROP TABLE project_sector');
        $this->addSql('DROP TABLE vulnerability_criterion');
        $this->addSql('DROP TABLE beneficiary');
        $this->addSql('DROP TABLE beneficiary_vulnerability_criterion');
        $this->addSql('DROP TABLE country_specific');
        $this->addSql('DROP TABLE profile');
        $this->addSql('DROP TABLE phone');
        $this->addSql('DROP TABLE household');
        $this->addSql('DROP TABLE household_project');
        $this->addSql('DROP TABLE national_id');
        $this->addSql('DROP TABLE country_specific_answer');
        $this->addSql('DROP TABLE selection_criteria');
        $this->addSql('DROP TABLE distribution_beneficiary');
        $this->addSql('DROP TABLE commodity');
        $this->addSql('DROP TABLE modality');
        $this->addSql('DROP TABLE modality_type');
        $this->addSql('DROP TABLE distribution_data');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE financial_provider');
        $this->addSql('DROP TABLE adm1');
        $this->addSql('DROP TABLE adm2');
        $this->addSql('DROP TABLE adm3');
        $this->addSql('DROP TABLE adm4');
        $this->addSql('DROP TABLE logs');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE reporting_project');
        $this->addSql('DROP TABLE reporting_indicator');
        $this->addSql('DROP TABLE reporting_value');
        $this->addSql('DROP TABLE reporting_distribution');
        $this->addSql('DROP TABLE reporting_country');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE booklet');
        $this->addSql('DROP TABLE booklet_product');
        $this->addSql('DROP TABLE vendor');
        $this->addSql('DROP TABLE voucher');
        $this->addSql('DROP TABLE voucher_product');
    }
}
