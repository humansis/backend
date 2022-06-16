<?php declare(strict_types=1);

namespace NewApiBundle\OutputType\Assistance;

use NewApiBundle\Request\InputTypeInterface;
use NewApiBundle\Utils\DateTime\Iso8601Converter;
use NewApiBundle\Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;


class DistributeReliefPackagesOutputType implements InputTypeInterface
{

    /**
     * @var array
     */
    private $successfullyDistributed = [];

    /**
     * @var array
     */
    private $partiallyDistributed = [];

    /**
     * @var array
     */
    private $failed = [];

    /**
     * @return array
     */
    public function getSuccessfullyDistributed(): array
    {
        return $this->successfullyDistributed;
    }

    /**
     * @param array $successfullyDistributed
     */
    public function setSuccessfullyDistributed(array $successfullyDistributed): void
    {
        $this->successfullyDistributed = $successfullyDistributed;
    }

    /**
     * @param $successfullyDistributedId
     */
    public function addSuccessfullyDistributed($successfullyDistributedId): void
    {
        $this->successfullyDistributed[] = $successfullyDistributedId;
    }

    /**
     * @return array
     */
    public function getPartiallyDistributed(): array
    {
        return $this->partiallyDistributed;
    }

    /**
     * @param array $partiallyDistributed
     */
    public function setPartiallyDistributed(array $partiallyDistributed): void
    {
        $this->partiallyDistributed = $partiallyDistributed;
    }

    /**
     * @param $partiallyDistributedId
     */
    public function addPartiallyDistributed($partiallyDistributedId): void
    {
        $this->partiallyDistributed[] = $partiallyDistributedId;
    }

    /**
     * @return array
     */
    public function getFailed(): array
    {
        return $this->failed;
    }

    /**
     * @param array $failed
     */
    public function setFailed(array $failed): void
    {
        $this->failed = $failed;
    }

    /**
     * @param $partiallyDistributedId
     */
    public function addFailed($failedId): void
    {
        $this->failed[] = $failedId;
    }



}
