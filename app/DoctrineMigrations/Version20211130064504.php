<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211130064504 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->connection->prepare("INSERT INTO bmstest.import_queue (id, import_id, file_id, content, state, message, identity_checked_at, similarity_checked_at) VALUES (52, null, null, '[{\"Adm1\": \"Banteay Meanchey\", \"Adm2\": null, \"Adm3\": null, \"Adm4\": null, \"Head\": \"true\", \"F 60+\": null, \"M 60+\": null, \"Notes\": null, \"Assets\": null, \"Gender\": \"Male\", \"F 0 - 2\": null, \"F 2 - 5\": null, \"ID Type\": \"National ID\", \"M 0 - 2\": null, \"M 2 - 5\": null, \"F 6 - 17\": null, \"Latitude\": null, \"M 6 - 17\": null, \"Camp name\": null, \"F 18 - 59\": 1, \"ID Number\": 98349834, \"Longitude\": null, \"M 18 - 59\": 1, \"Debt Level\": 3, \"Livelihood\": \"Government\", \"Tent number\": null, \"Income level\": null, \"Type phone 1\": \"Mobile\", \"Type phone 2\": null, \"Date of birth\": \"12-04-1990\", \"Proxy phone 1\": \"N\", \"Proxy phone 2\": null, \"Address number\": 123, \"Address street\": \"Fake St\", \"Number phone 1\": \"10834243\", \"Number phone 2\": null, \"Prefix phone 1\": \"+855\", \"Prefix phone 2\": null, \"Shelter status\": null, \"Address postcode\": 90210, \"Local given name\": \"John\", \"Residency status\": \"Resident\", \"Local family name\": \"Smith\", \"English given name\": null, \"English family name\": null, \"Support Date Received\": \"12-12-2020\", \"Food Consumption Score\": 3, \"Support Received Types\": \"MPCA\", \"Vulnerability criteria\": \"disabled\", \"Coping Strategies Index\": 2}]', 'New', null, null, null);")->execute();
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

            $this->addSql("UPDATE import_queue SET content='$serializedContent' WHERE id=$id");
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

    }
}
