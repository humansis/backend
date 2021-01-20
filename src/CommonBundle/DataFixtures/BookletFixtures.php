<?php

declare(strict_types=1);

namespace CommonBundle\DataFixtures;

use CommonBundle\Controller\CountryController;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\HttpKernel\Kernel;
use VoucherBundle\Utils\BookletService;

class BookletFixtures extends Fixture implements FixtureGroupInterface
{
    private $defaultBooklet = [
        "number_booklets" => 5,
        "individual_values" => [200, 200, 200],
        "number_vouchers" => 3,
    ];

    private $kernel;

    /** @var BookletService */
    private $bookletService;


    public function __construct(Kernel $kernel, BookletService $bookletService)
    {
        $this->kernel = $kernel;
        $this->bookletService = $bookletService;
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

        foreach (CountryController::COUNTRIES as $country) {
            $data = $this->defaultBooklet;
            $data['__country'] = $country['iso3'];
            $data['currency'] = $country['currency'];
            $this->bookletService->create($country['iso3'], $data);
        }
    }

    public static function getGroups(): array
    {
        return ['test'];
    }
}
