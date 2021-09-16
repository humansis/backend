<?php

declare(strict_types=1);

namespace CommonBundle\DataFixtures;

use BeneficiaryBundle\Entity\NationalId;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Entity\ImportQueue;
use ProjectBundle\Entity\Project;
use UserBundle\Entity\User;

class ImportFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $import = new Import('test_fixtures', null, $this->getProject($manager), $this->getUser($manager));
        $manager->persist($import);

        $file = new ImportFile('fake_file.xlsx', $import, $this->getUser($manager));
        $file->setIsLoaded(true);
        $manager->persist($file);

        $item = new ImportQueue($import, $file, [
            ['ID Type' => NationalId::TYPE_NATIONAL_ID, 'ID Number' => '123456789'],
            ['ID Type' => NationalId::TYPE_NATIONAL_ID, 'ID Number' => '111222333'],
        ]);
        $manager->persist($item);

        $item = new ImportQueue($import, $file, [
            ['ID Type' => NationalId::TYPE_NATIONAL_ID, 'ID Number' => '987654321'],
        ]);
        $manager->persist($item);

        $item = new ImportQueue($import, $file, [
            ['ID Type' => NationalId::TYPE_NATIONAL_ID, 'ID Number' => '111222333'],
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
        return $manager->getRepository(Project::class)->findBy(['iso3' => 'KHM'])[0];
    }

    private function getUser(ObjectManager $manager): User
    {
        return $manager->getRepository(User::class)->findBy([])[0];
    }
}
