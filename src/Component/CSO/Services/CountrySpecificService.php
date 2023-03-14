<?php

declare(strict_types=1);

namespace Component\CSO\Services;

use Component\CSO\Exception\CSOUpdateException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Entity\CountrySpecific;
use Exception;
use InputType\CountrySpecificCreateInputType;
use InputType\CountrySpecificUpdateInputType;
use Repository\CountrySpecificAnswerRepository;
use Utils\ExportService;

class CountrySpecificService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ExportService $exportService,
        private readonly ManagerRegistry $managerRegistry,
        private readonly CountrySpecificAnswerRepository $countrySpecificAnswerRepository,
    )
    {
    }

    /**
     * @throws UniqueConstraintViolationException
     */
    public function create(CountrySpecificCreateInputType $inputType): CountrySpecific
    {
        $countrySpecific = new CountrySpecific(
            $inputType->getField(),
            $inputType->getType(),
            $inputType->getIso3(),
            $inputType->getMultiValue(),
        );

        $this->managerRegistry->getManager()->persist($countrySpecific);
        $this->managerRegistry->getManager()->flush();

        return $countrySpecific;
    }

    /**
     * @throws UniqueConstraintViolationException
     */
    public function update(CountrySpecific $countrySpecific, CountrySpecificUpdateInputType $inputType): CountrySpecific
    {
        if ($countrySpecific->isMultiValue() && !$inputType->getMultiValue()) {
            if (!$this->canBeMigratedToSingleValue($countrySpecific)) {
                throw new CSOUpdateException('Cannot change to single value, because there are multiple values for this field');
            }
        }

        $countrySpecific->setFieldString($inputType->getField());
        $countrySpecific->setType($inputType->getType());
        $countrySpecific->setMultiValue($inputType->getMultiValue());

        $this->managerRegistry->getManager()->persist($countrySpecific);
        $this->managerRegistry->getManager()->flush();

        return $countrySpecific;
    }

    private function canBeMigratedToSingleValue(CountrySpecific $countrySpecific): bool
    {
        if (!$countrySpecific->isMultiValue()) {
            return true;
        }

        return !$this->countrySpecificAnswerRepository->hasMoreAnswers($countrySpecific);
    }

    public function delete(CountrySpecific $countrySpecific): bool
    {
        try {
            $this->em->remove($countrySpecific);
            $this->em->flush();
        } catch (Exception) {
            return false;
        }

        return true;
    }

    /**
     * Export all the countries specifics in a CSV file
     *
     * @return mixed
     */
    public function exportToCsv(string $type, string $countryIso3)
    {
        $exportableTable = $this->em->getRepository(CountrySpecific::class)->findBy(
            ['countryIso3' => $countryIso3],
            ['id' => 'asc']
        );

        return $this->exportService->export($exportableTable, 'country', $type);
    }
}
