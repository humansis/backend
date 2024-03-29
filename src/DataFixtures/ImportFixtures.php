<?php

declare(strict_types=1);

namespace DataFixtures;

use Entity\NationalId;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Entity\Import;
use Entity\ImportFile;
use Entity\ImportQueue;
use Enum\NationalIdType;
use Entity\Project;
use Entity\User;

class ImportFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $import = new Import('KHM', 'test_fixtures', null, [$this->getProject($manager)], $this->getUser($manager));
        $manager->persist($import);

        $file = new ImportFile('fake_file.xlsx', $import, $this->getUser($manager));
        $file->setIsLoaded(true);
        $manager->persist($file);

        $item = new ImportQueue($import, $file, [
            [
                'ID Type' => [
                    'value' => NationalIdType::NATIONAL_ID,
                    'dataType' => 's',
                    'numberFormat' => 'General',
                ],
                'ID Number' => [
                    'value' => '123456789',
                    'dataType' => 's',
                    'numberFormat' => 'General',
                ],
            ],
            [
                'ID Type' => [
                    'value' => NationalIdType::NATIONAL_ID,
                    'dataType' => 's',
                    'numberFormat' => 'General',
                ],
                'ID Number' => [
                    'value' => '111222333',
                    'dataType' => 's',
                    'numberFormat' => 'General',
                ],
            ],
        ]);
        $manager->persist($item);

        $item = new ImportQueue($import, $file, [
            [
                'ID Type' => [
                    'value' => NationalIdType::NATIONAL_ID,
                    'dataType' => 's',
                    'numberFormat' => 'General',
                ],
                'ID Number' => [
                    'value' => '987654321',
                    'dataType' => 's',
                    'numberFormat' => 'General',
                ],
            ],
        ]);
        $manager->persist($item);

        $item = new ImportQueue($import, $file, [
            [
                'ID Type' => [
                    'value' => NationalIdType::NATIONAL_ID,
                    'dataType' => 's',
                    'numberFormat' => 'General',
                ],
                'ID Number' => [
                    'value' => '111222333',
                    'dataType' => 's',
                    'numberFormat' => 'General',
                ],
            ],
        ]);
        $manager->persist($item);

        $manager->flush();
    }

    /**
     * @inheritDoc
     */
    public function getDependencies(): array
    {
        return [
            ProjectFixtures::class,
            UserFixtures::class,
        ];
    }

    private function getProject(ObjectManager $manager): Project
    {
        return $manager->getRepository(Project::class)->findBy(['countryIso3' => 'KHM'], ['id' => 'asc'])[0];
    }

    private function getUser(ObjectManager $manager): User
    {
        return $manager->getRepository(User::class)->findBy([], ['id' => 'asc'])[0];
    }
}
