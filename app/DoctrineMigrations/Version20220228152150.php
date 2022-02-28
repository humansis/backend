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
final class Version20220228152150 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $data = $this->connection->fetchAllAssociative('SELECT id, old_type, id_number FROM national_id');
        foreach ($data as $row) {
            $id = $row['id'];
            $oldValue = $row['old_type'];
            $number = $row['id_number'];

            if (str_starts_with($oldValue, '=IF(LEN(AL')) {
                if (strlen($number) > 7) {
                    $oldValue = NationalIdType::NATIONAL_ID;
                } else {
                    $oldValue = NationalIdType::FAMILY_BOOK;
                }
            }
            try {
                $newValue = NationalIdTypeEnum::valueToDB(NationalIdType::valueFromAPI($oldValue));
                $this->addSql("UPDATE national_id SET id_type=? WHERE id=$id", [$newValue]);
            } catch (EnumValueNoFoundException $e) {
                echo "ERROR in national ID transformation: $oldValue\n";
            }
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(true, 'Cant be downgraded');
    }
}
