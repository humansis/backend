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
use NewApiBundle\Component\Country\Countries;
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

    /** @var Countries */
    private $countries;

    public function __construct(Kernel $kernel, Countries $countries, BookletService $bookletService)
    {
        $this->kernel = $kernel;
        $this->bookletService = $bookletService;
        $this->countries = $countries;
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

        foreach ($this->countries->getAll() as $country) {
            $recipientCount = $manager->getRepository(Beneficiary::class)->countAllInCountry($country->getIso3());
            $project = $manager->getRepository(Project::class)->findOneBy(['iso3' => $country->getIso3()], ['id' => 'asc']);

            $count = 200;
            echo "{$country->getIso3()}: $count bnf: ";
            $data = $this->defaultBooklet;
            $data['__country'] = $country->getIso3();
            $data['currency'] = $country->getCurrency();
            $data['number_booklets'] = $count;
            $data['project_id'] = $project->getId();
            if ($recipientCount < 1) {
                echo "omitted\n";
                continue;
            }
            $this->bookletService->create($country->getIso3(), $data);
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
