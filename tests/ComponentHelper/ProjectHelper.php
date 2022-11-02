<?php

declare(strict_types=1);

namespace Tests\ComponentHelper;

use DateInterval;
use DateTime;
use Entity\Project;
use Entity\User;
use Exception;
use InputType\ProjectCreateInputType;
use Symfony\Component\DependencyInjection\Container;
use Utils\ProjectService;
use Utils\ValueGenerator\ValueGenerator;

/**
 * @property Container $container
 */
trait ProjectHelper
{
    /**
     * @throws Exception
     */
    public function createProject(User $user, ?ProjectCreateInputType $createInputType = null): Project
    {
        return self::$container->get(ProjectService::class)->create(
            $createInputType ?? self::getCreateInputType(),
            $user
        );
    }

    public static function getCreateInputType(string $iso3): ProjectCreateInputType
    {
        $createInputType = new ProjectCreateInputType();
        $createInputType->setIso3($iso3);
        $createInputType->setName('Test Project ' . ValueGenerator::int(1, 1000) . time());
        $createInputType->setStartDate((new DateTime())->format('Y-m-d'));
        $createInputType->setEndDate(((new DateTime())->add(new DateInterval("P1Y")))->format('Y-m-d'));
        $createInputType->setTarget(ValueGenerator::int(100, 1000));
        $createInputType->setProjectInvoiceAddressEnglish('1');
        $createInputType->setProjectInvoiceAddressLocal('1');

        return $createInputType;
    }
}
