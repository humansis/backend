<?php

namespace Utils;

use Entity\CountrySpecific;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class CountrySpecificService
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly ExportService $exportService)
    {
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
