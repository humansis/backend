<?php

declare(strict_types=1);

namespace Component\Auditor;

use DH\Auditor\Provider\Doctrine\Configuration;

class AuditorService
{
    /**
     * @var Configuration
     */
    private $doctrineConfiguration;

    /**
     * @var \DH\Auditor\Configuration
     */
    private $globalConfiguration;

    public function __construct(Configuration $doctrineConfiguration, \DH\Auditor\Configuration $globalConfiguration)
    {
        $this->doctrineConfiguration = $doctrineConfiguration;
        $this->globalConfiguration = $globalConfiguration;
    }

    /**
     * This function will disable auditing for entity or list of entities only for current php thread
     *
     * @param string|array $entity
     *
     * @return void
     */
    public function disableAuditForEntity($entity): void
    {
        if (is_array($entity)) {
            foreach ($entity as $entityName) {
                $this->doctrineConfiguration->disableAuditFor($entityName);
            }
        } else {
            $this->doctrineConfiguration->disableAuditFor($entity);
        }
    }

    /**
     * This function will disable auditing at all only for current php thread
     *
     * @return void
     */
    public function disableAuditing(): void
    {
        $this->globalConfiguration->disable();
    }
}
