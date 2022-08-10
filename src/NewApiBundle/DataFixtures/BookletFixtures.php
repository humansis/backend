<?php

declare(strict_types=1);

namespace NewApiBundle\DataFixtures;

use NewApiBundle\Repository\BeneficiaryRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use NewApiBundle\Component\Country\Countries;
use NewApiBundle\Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Kernel;
use VoucherBundle\Utils\BookletService;

class BookletFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    private $defaultBooklet = [
        "number_booklets" => 2,
        "individual_values" => [200, 400, 1000],
        "number_vouchers" => 2,
    ];

    private $kernel;

    /** @var BookletService */
    private $bookletService;

    /** @var Countries */
    private $countries;

    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    /**
     * @var BeneficiaryRepository
     */
    private $beneficiaryRepository;

    public function __construct(
        Kernel                $kernel,
        Countries             $countries,
        BookletService        $bookletService,
        ProjectRepository     $projectRepository,
        BeneficiaryRepository $beneficiaryRepository
    ) {
        $this->kernel = $kernel;
        $this->bookletService = $bookletService;
        $this->countries = $countries;
        $this->projectRepository = $projectRepository;
        $this->beneficiaryRepository = $beneficiaryRepository;
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
            $recipientCount = $this->beneficiaryRepository->countAllInCountry($country->getIso3());
            $project = $this->projectRepository->findOneBy(['iso3' => $country->getIso3()], ['id' => 'asc']);

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
