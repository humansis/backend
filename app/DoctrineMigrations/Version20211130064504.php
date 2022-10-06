<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211130064504 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $data = $this->connection->fetchAllAssociative('SELECT id, content FROM import_queue');

        foreach ($data as $row) {
            $id = $row['id'];

            $content = json_decode($row['content'], true);

            foreach ($content as $beneficiaryNo => $beneficiary) {
                foreach ($beneficiary as $attribute => $value) {
                    if (null === $value) {
                        continue;
                    }

                    $content[$beneficiaryNo][$attribute] = [
                        'value' => $value,
                        'dataType' => 's',
                        'numberFormat' => 'General',
                    ];
                }
            }

            $serializedContent = json_encode($content);

            $this->addSql("UPDATE import_queue SET content=? WHERE id=$id", [$serializedContent]);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
