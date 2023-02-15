<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Rector\StaticPropertyFetch\KernelTestCaseContainerPropertyDeprecationRector;
use Rector\Symfony\Set\FOSRestSetList;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/tests',
    ]);

    $rectorConfig->symfonyContainerXml(__DIR__ . '/var/cache/local/appAppKernelLocalDebugContainer.xml');

    $rectorConfig->sets([
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SensiolabsSetList::ANNOTATIONS_TO_ATTRIBUTES,
        FOSRestSetList::ANNOTATIONS_TO_ATTRIBUTES
    ]);

    // define sets of rules
    $rectorConfig->rules([
        KernelTestCaseContainerPropertyDeprecationRector::class,
    ]);
};
