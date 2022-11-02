<?php

declare(strict_types=1);

namespace OutputType\Assistance;

use Entity\Beneficiary;
use Request\InputTypeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AssistanceBeneficiaryOperationOutputType implements InputTypeInterface
{
    private $documentNumbers;

    private array $notFound = [];

    private array $success = [];

    private array $alreadyRemoved = [];

    private array $failed = [];

    /**
     * @param array|null $documentNumbers
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        array $documentNumbers = [],
        private readonly ?string $documentType = null
    ) {
        $this->documentNumbers = array_map(fn($number) => strtolower((string) $number), $documentNumbers);
    }

    public function getNotFound(): array
    {
        return $this->notFound;
    }

    public function addNotFound($notFound): AssistanceBeneficiaryOperationOutputType
    {
        $this->notFound[] = $notFound;

        return $this;
    }

    public function addDocumentNotFound(string $number): AssistanceBeneficiaryOperationOutputType
    {
        $this->notFound[] = [
            'documentNumber' => $number,
            'message' => $this->translator->trans('Beneficiary')
                . " ({$this->documentType} '{$number}') "
                . $this->translator->trans('was not found in the assistance.'),
        ];
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

    public function setNotFound(array $notFound): AssistanceBeneficiaryOperationOutputType
    {
        $this->notFound = $notFound;

        return $this;
    }

    public function getSuccess(): array
    {
        return $this->success;
    }

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

    public function getFailed(): array
    {
        return $this->failed;
    }

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

    public function setAlreadyRemoved(array $alreadyRemoved): AssistanceBeneficiaryOperationOutputType
    {
        $this->alreadyRemoved = $alreadyRemoved;

        return $this;
    }
}
