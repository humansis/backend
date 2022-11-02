<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version0000 extends AbstractMigration implements ContainerAwareInterface
{
    private ?\Symfony\Component\DependencyInjection\ContainerInterface $container = null;

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        $databaseUser = $this->container->getParameter('database_user');

        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'CREATE DEFINER=' . $databaseUser . '@`%` FUNCTION `LEVENSHTEIN`(`s1` VARCHAR(255), `s2` VARCHAR(255)) RETURNS INT(11) NOT DETERMINISTIC CONTAINS SQL SQL SECURITY DEFINER BEGIN
            DECLARE s1_len, s2_len, i, j, c, c_temp, cost INT;
            DECLARE s1_char CHAR;
            DECLARE cv0, cv1 VARBINARY(256);
            SET s1_len = CHAR_LENGTH(s1), s2_len = CHAR_LENGTH(s2), cv1 = 0x00, j = 1, i = 1, c = 0;
            IF s1 = s2 THEN
                RETURN 0;
            ELSEIF s1_len = 0 THEN
                RETURN s2_len;
            ELSEIF s2_len = 0 THEN
                RETURN s1_len;
            ELSE
                WHILE j <= s2_len DO
                    SET cv1 = CONCAT(cv1, UNHEX(HEX(j))), j = j + 1;
                END WHILE;
                WHILE i <= s1_len DO
                    SET s1_char = SUBSTRING(s1, i, 1), c = i, cv0 = UNHEX(HEX(i)), j = 1;
                    WHILE j <= s2_len DO
                        SET c = c + 1;
                        IF s1_char = SUBSTRING(s2, j, 1) THEN SET cost = 0; ELSE SET cost = 1; END IF;
                        SET c_temp = CONV(HEX(SUBSTRING(cv1, j, 1)), 16, 10) + cost;
                        IF c > c_temp THEN SET c = c_temp; END IF;
                        SET c_temp = CONV(HEX(SUBSTRING(cv1, j+1, 1)), 16, 10) + 1;
                        IF c > c_temp THEN SET c = c_temp; END IF;
                        SET cv0 = CONCAT(cv0, UNHEX(HEX(c))), j = j + 1;
                    END WHILE;
                    SET cv1 = cv0, i = i + 1;
                END WHILE;
            END IF;
            RETURN c;
        END'
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP FUNCTION [ IF EXISTS ] { [ ' . $schema->getName() . '. ] `LEVENSHTEIN` }');
    }
}
