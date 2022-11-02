<?php

declare(strict_types=1);

namespace DataFixtures;

use Repository\BeneficiaryRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Component\Country\Countries;
use Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Kernel;
use Utils\BookletService;

class BookletFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    private array $defaultBooklet = [
        "number_booklets" => 2,
        "individual_values" => [200, 400, 1000],
        "number_vouchers" => 2,
    ];

    public function __construct(private readonly Kernel $kernel, private readonly Countries $countries, private readonly BookletService $bookletService, private readonly ProjectRepository $projectRepository, private readonly BeneficiaryRepository $beneficiaryRepository)
    {
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @throws Exception
     */
    public function load(ObjectManager $manager)
    {
        if ($this->kernel->getEnvironment() === "prod") {
            echo self::class . " can't be running at production\n";

            return;
        }

        foreach ($this->countries->getAll() as $country) {
            $recipientCount = $this->beneficiaryRepository->countAllInCountry($country->getIso3());
            $project = $this->projectRepository->findOneBy(['countryIso3' => $country->getIso3()], ['id' => 'asc']);

            $count = 50;
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
