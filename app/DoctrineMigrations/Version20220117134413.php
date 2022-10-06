<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220117134413 extends AbstractMigration
{
    private function normalizeValue($value): string
    {
        if (is_string($value)) {
            $lowered = mb_strtolower($value);

            //removes every character which is not a number or a letter
            return preg_replace('|[\W_]+|', '', $lowered);
        }
        if (is_bool($value)) {
            return $value === true ? 'true' : 'false';
        }

        return (string)$value;
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE location ADD enum_normalized_name VARCHAR(255)');

        $data = $this->connection->fetchAllAssociative('SELECT id, name FROM location');

        foreach ($data as $row) {
            $this->addSql("UPDATE location SET enum_normalized_name=? WHERE id=?", [$this->normalizeValue($row['name']), $row['id']]);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE location DROP enum_normalized_name');
    }
}
