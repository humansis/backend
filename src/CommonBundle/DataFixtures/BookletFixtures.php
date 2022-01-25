<?php

declare(strict_types=1);

namespace CommonBundle\DataFixtures;

use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\Assistance;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpKernel\Kernel;
use VoucherBundle\Utils\BookletService;

class BookletFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    private $defaultBooklet = [
        "number_booklets" => 3,
        "individual_values" => [200, 400, 1000],
        "number_vouchers" => 3,
    ];

    private $kernel;

    /** @var BookletService */
    private $bookletService;

    private $countries = [];

    public function __construct(Kernel $kernel, array $countries, BookletService $bookletService)
    {
        $this->kernel = $kernel;
        $this->bookletService = $bookletService;

        foreach ($countries as $country) {
            $this->countries[$country['iso3']] = $country;
        }
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws Exception
     */
    public function load(ObjectManager $manager)
    {
        if ($this->kernel->getEnvironment() === "prod") {
            echo __CLASS__ . " can't be running at production\n";
            return;
        }

        foreach ($this->countries as $country) {
            $recipientCount = $manager->getRepository(Beneficiary::class)->countAllInCountry($country['iso3']);
            $project = $manager->getRepository(Project::class)->findOneBy(['iso3' => $country['iso3']], ['id' => 'asc']);
            $voucherAssistanceCount = count($manager->getRepository(Assistance::class)->getActiveByCountry($country['iso3']));

            $count = 200;
            echo "{$country['iso3']}: $count bnf: ";
            $data = $this->defaultBooklet;
            $data['__country'] = $country['iso3'];
            $data['currency'] = $country['currency'];
            $data['number_booklets'] = $count;
            $data['project_id'] = $project->getId();
            if ($recipientCount < 1) {
                echo "omitted\n";
                continue;
            }
            $this->bookletService->create($country['iso3'], $data);
            echo "generated\n";
        }
    }

    public static function getGroups(): array
    {
        return ['preview'];
    }

    public function getDependencies(): array
    {
        return [
            BeneficiaryTestFixtures::class,
        ];
    }
}
