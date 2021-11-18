<?php
declare(strict_types=1);
namespace NewApiBundle\Component\WorkflowDoc;

class Entity
{
    /**
     * @var string
     */
    private $entityName;

    /**
     * @var string
     */
    private $entityFullName;

    /**
     * @var string
     */
    private $bundleName;

    /**
     * @var string
     */
    private $entityDescription;

    /**
     * @var array
     */
    private $supportedWorkflows;

    public function __construct(string $entityName, string $entityFullName, string $bundleName)
    {
        $this->entityName = $entityName;
        $this->entityFullName = $entityFullName;
        $this->bundleName = $bundleName;
        $this->supportedWorkflows = [];
        $this->entityDescription = 'Lorem ipsum dolor sit amet.';
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @return string
     */
    public function getEntityFullName()
    {
        return $this->entityFullName;
    }

    /**
     * @return string
     */
    public function getBundleName(): string
    {
        return $this->bundleName;
    }

    /**
     * @return array
     */
    public function getSupportedWorkflows(): array
    {
        return $this->supportedWorkflows;
    }

    /**
     * @param string $supportedWorkflow
     */
    public function addSupportedWorkflows(string $supportedWorkflow): void
    {
        array_push($this->supportedWorkflows,$supportedWorkflow);
    }

    /**
     * @return string
     */
    public function getEntityDescription(): string
    {
        return $this->entityDescription;
    }

    /**
     * @param string $entityDescription
     */
    public function setEntityDescription(string $entityDescription): void
    {
        $this->entityDescription = $entityDescription;
    }

    /**
     * @return bool
     */
    public function isSupported($workflowEntity): bool
    {
        if (strcmp($this->entityFullName, $workflowEntity) == 0) {
            return true;
        }
        else {
            return false;
        }
    }

}