<?php

declare(strict_types=1);

namespace OutputType\Assistance;

use Request\InputTypeInterface;
use Utils\DateTime\Iso8601Converter;
use Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

class DistributeReliefPackagesOutputType implements InputTypeInterface
{
    private array $notFound = [];

    private array $conflicts = [];

    private array $successfullyDistributed = [];

    private array $partiallyDistributed = [];

    private array $alreadyDistributed = [];

    private array $failed = [];

    public function getSuccessfullyDistributed(): array
    {
        return $this->successfullyDistributed;
    }

    public function setSuccessfullyDistributed(array $successfullyDistributed): DistributeReliefPackagesOutputType
    {
        $this->successfullyDistributed = $successfullyDistributed;

        return $this;
    }

    /**
     * @param      $successfullyDistributedId
     * @param null $beneficiaryId
     */
    public function addSuccessfullyDistributed(
        $successfullyDistributedId,
        $beneficiaryId = null,
        $idNumber = null
    ): DistributeReliefPackagesOutputType {
        $this->successfullyDistributed[] = [
            'reliefPackageId' => $successfullyDistributedId,
            'beneficiaryId' => $beneficiaryId,
            'idNumber' => $idNumber,
        ];

        return $this;
    }

    public function getPartiallyDistributed(): array
    {
        return $this->partiallyDistributed;
    }

    public function setPartiallyDistributed(array $partiallyDistributed): DistributeReliefPackagesOutputType
    {
        $this->partiallyDistributed = $partiallyDistributed;

        return $this;
    }

    /**
     * @param      $partiallyDistributedId
     * @param null $beneficiaryId
     */
    public function addPartiallyDistributed(
        $partiallyDistributedId,
        $beneficiaryId = null,
        $idNumber = null
    ): DistributeReliefPackagesOutputType {
        $this->partiallyDistributed[] = [
            'reliefPackageId' => $partiallyDistributedId,
            'beneficiaryId' => $beneficiaryId,
            'idNumber' => $idNumber,
        ];

        return $this;
    }

    public function getAlreadyDistributed(): array
    {
        return $this->alreadyDistributed;
    }

    public function setAlreadyDistributed(array $alreadyDistributed): void
    {
        $this->alreadyDistributed = $alreadyDistributed;
    }

    /**
     * @param      $alreadyDistributedId
     * @param null $beneficiaryId
     */
    public function addAlreadyDistributed(
        $alreadyDistributedId,
        $beneficiaryId = null,
        $idNumber = null
    ): DistributeReliefPackagesOutputType {
        $this->alreadyDistributed[] = [
            'reliefPackageId' => $alreadyDistributedId,
            'beneficiaryId' => $beneficiaryId,
            'idNumber' => $idNumber,
        ];

        return $this;
    }

    public function getFailed(): array
    {
        return $this->failed;
    }

    public function setFailed(array $failed): DistributeReliefPackagesOutputType
    {
        $this->failed = $failed;

        return $this;
    }

    /**
     * @param $failedId
     * @param $message
     */
    public function addFailed($failedId, $message = null): DistributeReliefPackagesOutputType
    {
        $this->failed[] = ['reliefPackageId' => $failedId, 'error' => $message];

        return $this;
    }

    public function getNotFound(): array
    {
        return $this->notFound;
    }

    public function setNotFound(array $notFound): DistributeReliefPackagesOutputType
    {
        $this->notFound = $notFound;

        return $this;
    }

    /**
     * @param $notFoundId
     */
    public function addNotFound($notFound): DistributeReliefPackagesOutputType
    {
        $this->notFound[] = ['idNumber' => $notFound];

        return $this;
    }

    public function getConflicts(): array
    {
        return $this->conflicts;
    }

    public function setConflictIds(array $conflicts): DistributeReliefPackagesOutputType
    {
        $this->conflicts = $conflicts;

        return $this;
    }

    /**
     * @param $idNumber
     * @param $beneficiaries
     */
    public function addConflictId($idNumber, $beneficiaries): DistributeReliefPackagesOutputType
    {
        $this->conflicts[] = ['idNumber' => $idNumber, 'beneficiaries' => $beneficiaries];

        return $this;
    }
}
