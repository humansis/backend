<?php

namespace DataFixtures;

use DateInterval;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Enum\ProductCategoryType;
use DBAL\SectorEnum;
use Entity\Project;
use Exception;
use Symfony\Component\HttpKernel\Kernel;

class ProjectFixtures extends Fixture implements FixtureGroupInterface
{
    private $countries = ["KHM", "UKR", "SYR", "ETH", "MNG", "ARM", "ZMB"];

    private const PROJECT_NAME = 0;
    private const PROJECT_NUMBER_OF_HOUSEHOLDS = 1;
    private const PROJECT_TARGET = 2;
    private const PROJECT_NOTES = 3;
    private const PROJECT_ISO3 = 4;
    private const PROJECT_PROJECT_INVOICE_ADDRESS_ENGLISH = 5;
    private const PROJECT_PROJECT_INVOICE_ADDRESS_LOCAL = 6;
    private const PROJECT_ALLOWED_PRODUCT_CATEGORY_TYPES = 7;

    private $explicitTestProjects = [
        ['Dev KHM Project', 1, 1, 'notes', 'KHM', 'KHM eng address', 'KHM local address', [ProductCategoryType::FOOD]],
        ['Dev UKR Project', 1, 1, 'notes', 'UKR', 'UKR eng address', 'UKR local address', [ProductCategoryType::FOOD]],
        ['Dev SYR Project', 1, 1, 'notes', 'SYR', 'SYR eng address', 'SYR local address', [ProductCategoryType::FOOD]],
        ['Dev ETH Project', 1, 1, 'notes', 'ETH', 'ETH eng address', 'ETH local address', [ProductCategoryType::FOOD]],
        ['Dev MNG Project', 1, 1, 'notes', 'MNG', 'MNG eng address', 'MNG local address', [ProductCategoryType::FOOD]],
        ['Dev ARM Project', 1, 1, 'notes', 'ARM', 'ARM eng address', 'ARM local address', [ProductCategoryType::FOOD]],
        ['Dev ZMB Project', 1, 1, 'notes', 'ZMB', 'ZMB eng address', 'ZMB local address', [ProductCategoryType::FOOD]],
    ];

    private $countryProjectNameTemplate = "{adjective} test project";

    private $countryNameAdjectives = [
        'KHM' => 'Cambodian',
        'SYR' => 'Syrian',
        'UKR' => 'Ukrainian',
        'ETH' => 'Ethiopian',
        'MNG' => 'Mongolian',
        'ARM' => 'Armenian',
        'ZMB' => 'Zambian',
    ];

    private $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     *
     * @throws Exception
     */
    public function load(ObjectManager $manager)
    {
        if ($this->kernel->getEnvironment() === "prod") {
            echo __CLASS__ . " can't be running at production\n";

            return;
        }

        foreach ($this->explicitTestProjects as $datum) {
            $this->createProjectFromData($manager, $datum);
        }
        $manager->flush();

        foreach ($this->countries as $country) {
            $projectName = str_replace(
                '{adjective}',
                $this->countryNameAdjectives[$country],
                $this->countryProjectNameTemplate
            );
            $this->createProjectFromData(
                $manager,
                [
                    $projectName,
                    1,
                    0,
                    'notes',
                    $country,
                    "$country eng address",
                    "$country local address",
                    [ProductCategoryType::FOOD],
                ]
            );
        }
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param               $country
     * @param array $data
     */
    private function createProjectFromData(ObjectManager $manager, array $data): void
    {
        $project = $manager->getRepository(Project::class)->findOneByName($data[0]);
        if ($project instanceof Project) {
            echo "User {$project->getName()} in {$project->getCountryIso3()} already exists. Omit creation.\n";
        } else {
            $project = new Project();
            $project->setName($data[self::PROJECT_NAME])
                ->setStartDate(new DateTime())
                ->setEndDate((new DateTime())->add(new DateInterval("P1Y")))
                ->setNumberOfHouseholds($data[self::PROJECT_NUMBER_OF_HOUSEHOLDS])
                ->setTarget($data[self::PROJECT_TARGET])
                ->setNotes($data[self::PROJECT_NOTES])
                ->setCountryIso3($data[self::PROJECT_ISO3])
                ->setProjectInvoiceAddressEnglish($data[self::PROJECT_PROJECT_INVOICE_ADDRESS_ENGLISH])
                ->setProjectInvoiceAddressLocal($data[self::PROJECT_PROJECT_INVOICE_ADDRESS_LOCAL])
                ->setAllowedProductCategoryTypes($data[self::PROJECT_ALLOWED_PRODUCT_CATEGORY_TYPES])
                ->setSectors(SectorEnum::all());

            $manager->persist($project);
            echo $project->getName() . " created\n";
        }
    }

    public static function getGroups(): array
    {
        return ['test'];
    }
}
