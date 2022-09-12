<?php declare(strict_types=1);

namespace NewApiBundle\OutputType\Assistance;

use BeneficiaryBundle\Entity\Beneficiary;
use NewApiBundle\Request\InputTypeInterface;

class AssistanceBeneficiaryOperationOutputType implements InputTypeInterface
{

    private $documentNumbers;

    private $documentType;

    /**
     * @var array
     */
    private $notFound = [];

    /**
     * @var array
     */
    private $success = [];

    /**
     * @var array
     */
    private $alreadyRemoved = [];

    /**
     * @var array
     */
    private $failed = [];

    /**
     * @param array $documentNumbers
     * @param string $documentType
     */
    public function __construct(array $documentNumbers,string $documentType)
    {
        $this->documentNumbers = $documentNumbers;
        $this->documentType = $documentType;
    }


    /**
     * @return array
     */
    public function getNotFound(): array
    {
        return $this->notFound;
    }

    public function addNotFound($notFound): AssistanceBeneficiaryOperationOutputType
    {
        $this->notFound[] = $notFound;
        return $this;
    }

    public function addBeneficiaryNotFound(Beneficiary $beneficiary): AssistanceBeneficiaryOperationOutputType
    {
        $number = $this->getInputIdNumber($beneficiary, $this->documentNumbers, $this->documentType);
        $this->notFound[] = [
            'documentNumber' => $number,
            'beneficiaryId' => $beneficiary->getId(),
            'message' => "BNF with {$this->documentType} '{$number}' was found but he is not in assistance."
        ];
        return $this;
    }

    /**
     * @param array $notFound
     *
     * @return AssistanceBeneficiaryOperationOutputType
     */
    public function setNotFound(array $notFound): AssistanceBeneficiaryOperationOutputType
    {
        $this->notFound = $notFound;

        return $this;
    }

    /**
     * @return array
     */
    public function getSuccess(): array
    {
        return $this->success;
    }

    /**
     * @param array $success
     *
     * @return AssistanceBeneficiaryOperationOutputType
     */
    public function setSuccess(array $success): AssistanceBeneficiaryOperationOutputType
    {
        $this->success = $success;

        return $this;
    }

    public function addSuccess($success): AssistanceBeneficiaryOperationOutputType
    {
        $this->success[] = $success;
        return $this;
    }

    public function addBeneficiarySuccess(Beneficiary $beneficiary): AssistanceBeneficiaryOperationOutputType
    {
        $number = $this->getInputIdNumber($beneficiary, $this->documentNumbers, $this->documentType);
        $this->success[] = [
            'documentNumber' => $number,
            'beneficiaryId' => $beneficiary->getId(),
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
     *
     * @return AssistanceBeneficiaryOperationOutputType
     */
    public function setFailed(array $failed): AssistanceBeneficiaryOperationOutputType
    {
        $this->failed = $failed;

        return $this;
    }

    public function addFailed(array $failed): AssistanceBeneficiaryOperationOutputType
    {
        $this->failed[] = $failed;
        return $this;
    }

    public function addBeneficiaryFailed(Beneficiary $beneficiary, $message): AssistanceBeneficiaryOperationOutputType
    {
        $number = $this->getInputIdNumber($beneficiary, $this->documentNumbers, $this->documentType);
        $this->failed[] = [
            'documentNumber' => $number,
            'beneficiaryId' => $beneficiary->getId(),
            'message' => $message
        ];
        return $this;
    }


    private function getInputIdNumber(Beneficiary $beneficiary, $numbers, $idType)
    {
        foreach ($beneficiary->getNationalIds() as $document) {
            if ($document->getIdType() === $idType && in_array($document->getIdNumber(), $numbers)) {
                return $document->getIdNumber();
            }
        }
        return null;
    }

    /**
     * @return array
     */
    public function getAlreadyRemoved(): array
    {
        return $this->alreadyRemoved;
    }

    public function addBeneficiaryAlreadyRemoved(Beneficiary $beneficiary): AssistanceBeneficiaryOperationOutputType
    {
        $number = $this->getInputIdNumber($beneficiary, $this->documentNumbers, $this->documentType);
        $this->alreadyRemoved[] = [
            'documentNumber' => $number,
            'beneficiaryId' => $beneficiary->getId(),
        ];
        return $this;
    }

    /**
     * @param array $alreadyRemoved
     *
     * @return AssistanceBeneficiaryOperationOutputType
     */
    public function setAlreadyRemoved(array $alreadyRemoved): AssistanceBeneficiaryOperationOutputType
    {
        $this->alreadyRemoved = $alreadyRemoved;

        return $this;
    }





}
