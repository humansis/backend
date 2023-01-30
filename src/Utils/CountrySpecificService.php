<?php

namespace Utils;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Entity\CountrySpecific;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class CountrySpecificService
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly ExportService $exportService, private readonly ManagerRegistry $managerRegistry)
    {
    }

    /**
     * @throws UniqueConstraintViolationException
     */
    public function create($inputType): CountrySpecific
    {
        $countrySpecific = new CountrySpecific($inputType->getField(), $inputType->getType(), $inputType->getIso3());

        $this->managerRegistry->getManager()->persist($countrySpecific);
        $this->managerRegistry->getManager()->flush();

        return $countrySpecific;
    }

    /**
     * @throws UniqueConstraintViolationException
     */
    public function update($countrySpecific, $inputType): CountrySpecific
    {
        $countrySpecific->setFieldString($inputType->getField());
        $countrySpecific->setType($inputType->getType());

        $this->managerRegistry->getManager()->persist($countrySpecific);
        $this->managerRegistry->getManager()->flush();

        return $countrySpecific;
    }
    /**
     * @return bool
     */
    public function delete(CountrySpecific $countrySpecific)
    {
        try {
            $this->em->remove($countrySpecific);
            $this->em->flush();
        } catch (Exception) {
            return false;
        }

        return true;
    }
}
