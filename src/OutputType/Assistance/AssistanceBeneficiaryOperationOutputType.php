<?php

declare(strict_types=1);

namespace OutputType\Assistance;

use Entity\Beneficiary;
use Request\InputTypeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param array|null $documentNumbers
     * @param string|null $documentType
     */
    public function __construct(
        TranslatorInterface $translator,
        array $documentNumbers = [],
        string $documentType = null
    ) {
        $this->documentNumbers = array_map(function ($number) {
            return strtolower($number);
        }, $documentNumbers);
        $this->documentType = $documentType;
        $this->translator = $translator;
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
            'message' => $this->translator->trans('Beneficiary')
                . " ({$this->documentType} '{$number}') "
                . $this->translator->trans('was not found in the assistance.'),
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

    /**
     * @param Beneficiary $beneficiary
     * @param string $message
     *
     * @return $this
     */
    public function addBeneficiaryFailed(
        Beneficiary $beneficiary,
        string $message
    ): AssistanceBeneficiaryOperationOutputType {
        $documentNumber = $this->getInputIdNumber($beneficiary, $this->documentNumbers, $this->documentType);
        $this->failed[] = [
            'documentNumber' => $documentNumber,
            'beneficiaryId' => $beneficiary->getId(),
            'message' => $message,
        ];

        return $this;
    }

    /**
     * @param Beneficiary $beneficiary
     * @param array|null $documentNumbers
     * @param string|null $documentType
     *
     * @return string|null
     */
    private function getInputIdNumber(Beneficiary $beneficiary, ?array $documentNumbers, ?string $documentType): ?string
    {
        if ($documentNumbers === null || $documentType === null) {
            return null;
        }
        foreach ($beneficiary->getNationalIds() as $document) {
            $normalizedDocumentNumber = strtolower($document->getIdNumber());
            if ($document->getIdType() === $documentType && in_array($normalizedDocumentNumber, $documentNumbers)) {
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

    public function addBeneficiaryMismatch(Beneficiary $beneficiary): AssistanceBeneficiaryOperationOutputType
    {
        $number = $this->getInputIdNumber($beneficiary, $this->documentNumbers, $this->documentType);
        $this->notFound[] = [
            'documentNumber' => $number,
            'beneficiaryId' => $beneficiary->getId(),
            'message' => $this->translator->trans('Beneficiary')
                . " ({$this->documentType} '{$number}') "
                . $this->translator->trans(
                    'cannot be removed from assistance: Assistance is targeted to households and the beneficiary is not household head.'
                ),
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
