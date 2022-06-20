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
    private $notFound = [];

    /**
     * @var array
     */
    private $conflicts = [];

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
    private $alreadyDistributed = [];

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
     *
     * @return $this
     */
    public function setSuccessfullyDistributed(array $successfullyDistributed): DistributeReliefPackagesOutputType
    {
        $this->successfullyDistributed = $successfullyDistributed;
        return $this;
    }

    /**
     * @param      $successfullyDistributedId
     * @param null $beneficiaryId
     *
     * @return $this
     */
    public function addSuccessfullyDistributed($successfullyDistributedId, $beneficiaryId = NULL, $idNumber = NULL): DistributeReliefPackagesOutputType
    {
        $this->successfullyDistributed[] = [
            'reliefPackageId' => $successfullyDistributedId,
            'beneficiaryId' => $beneficiaryId,
            'idNumber' => $idNumber,
        ];
        return $this;
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
     *
     * @return $this
     */
    public function setPartiallyDistributed(array $partiallyDistributed): DistributeReliefPackagesOutputType
    {
        $this->partiallyDistributed = $partiallyDistributed;
        return $this;
    }

    /**
     * @param      $partiallyDistributedId
     * @param null $beneficiaryId
     *
     * @return $this
     */
    public function addPartiallyDistributed($partiallyDistributedId, $beneficiaryId = NULL, $idNumber = NULL): DistributeReliefPackagesOutputType
    {
        $this->partiallyDistributed[] = [
            'reliefPackageId' => $partiallyDistributedId,
            'beneficiaryId' => $beneficiaryId,
            'idNumber' => $idNumber,
        ];
        return $this;
    }

    /**
     * @return array
     */
    public function getAlreadyDistributed(): array
    {
        return $this->alreadyDistributed;
    }

    /**
     * @param array $alreadyDistributed
     */
    public function setAlreadyDistributed(array $alreadyDistributed): void
    {
        $this->alreadyDistributed = $alreadyDistributed;
    }

    /**
     * @param      $alreadyDistributedId
     * @param null $beneficiaryId
     *
     * @return $this
     */
    public function addAlreadyDistributed($alreadyDistributedId, $beneficiaryId = NULL, $idNumber = NULL): DistributeReliefPackagesOutputType
    {
        $this->alreadyDistributed[] = [
            'reliefPackageId' => $alreadyDistributedId,
            'beneficiaryId' => $beneficiaryId,
            'idNumber' => $idNumber,
        ];
        return $this;
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
    public function setFailed(array $failed): DistributeReliefPackagesOutputType
    {
        $this->failed = $failed;
        return $this;
    }

    /**
     * @param $failedId
     * @param $message
     *
     * @return $this
     */
    public function addFailed($failedId, $message = NULL): DistributeReliefPackagesOutputType
    {
        $this->failed[] = ['reliefPackageId' => $failedId, 'error' => $message];
        return $this;
    }

    /**
     * @return array
     */
    public function getNotFound(): array
    {
        return $this->notFound;
    }

    /**
     * @param array $notFound
     */
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

    /**
     * @return array
     */
    public function getConflicts(): array
    {
        return $this->conflicts;
    }

    /**
     * @param array $conflicts
     */
    public function setConflictIds(array $conflicts): DistributeReliefPackagesOutputType
    {
        $this->conflicts = $conflicts;
        return $this;
    }

    /**
     * @param $idNumber
     * @param $beneficiaries
     *
     * @return $this
     */
    public function addConflictId($idNumber, $beneficiaries): DistributeReliefPackagesOutputType
    {
        $this->conflicts[] = ['idNumber' => $idNumber, 'beneficiaries' => $beneficiaries];
        return $this;
    }




}
