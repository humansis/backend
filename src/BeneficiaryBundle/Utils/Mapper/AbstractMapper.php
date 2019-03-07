<?php


namespace BeneficiaryBundle\Utils\Mapper;


use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractMapper
{
    /** @var EntityManagerInterface $em */
    protected $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    protected $moreThanZ = false;

    /**
     * Load the mapping CSV for a specific country. Some columns can move because on the number of country specifics
     *
     * @param $countryIso3
     * @return array
     */
    protected function loadMappingCSVOfCountry($countryIso3)
    {
        /** @var CountrySpecific[] $countrySpecifics */
        $countrySpecifics = $this->em->getRepository(CountrySpecific::class)->findByCountryIso3($countryIso3);
        // Get the number of country specific for the specific country countryIso3
        $nbCountrySpecific = count($countrySpecifics);
        $mappingCSVCountry = [];
        $countrySpecificsAreLoaded = false;
        foreach (Household::MAPPING_CSV as $indexFormatted => $indexCSV)
        {
            // For recursive array (allowed only 1 level of recursivity)
            if (is_array($indexCSV))
            {
                foreach ($indexCSV as $indexFormatted2 => $indexCSV2)
                {
                    // If the column is before the non-static columns, change nothing
                    if ($indexCSV2 < Household::firstColumnNonStatic && !$countrySpecificsAreLoaded)
                        $mappingCSVCountry[$indexFormatted][$indexFormatted2] = $indexCSV2;
                    // Else we increment the column.
                    // Example : if $nbCountrySpecific = 1, we shift the column by 1 (if the column is X, it will became Y)
                    else
                    {
                        // If we have not added the country specific column in the mapping
                        if (!$countrySpecificsAreLoaded)
                        {
                            // Add each country specific column in the mapping
                            for ($i = 0; $i < $nbCountrySpecific; $i++)
                            {
                                $mappingCSVCountry["tmp_country_specific" . $i] =
                                    $this->SUMOfLetter($indexCSV2, $i);
                            }
                            $countrySpecificsAreLoaded = true;
                        }
                        $mappingCSVCountry[$indexFormatted][$indexFormatted2] = $this->SUMOfLetter($indexCSV2, $nbCountrySpecific);
                    }
                }
            }
            else
            {
                // Same process than in the if
                if ($indexCSV < Household::firstColumnNonStatic)
                    $mappingCSVCountry[$indexFormatted] = $indexCSV;
                else
                {
                    // If we have not added the country specific column in the mapping
                    if (!$countrySpecificsAreLoaded)
                    {
                        // Add each country specific column in the mapping
                        for ($i = 0; $i < $nbCountrySpecific; $i++)
                        {
                            $mappingCSVCountry["tmp_country_specific" . $i] =
                                $this->SUMOfLetter($indexCSV, $i);
                        }
                        $countrySpecificsAreLoaded = true;
                    }
                    $mappingCSVCountry[$indexFormatted] = $this->SUMOfLetter($indexCSV, $nbCountrySpecific);
                }
            }
        }

        return $mappingCSVCountry;
    }

    /**
     * Make an addition of a letter and a number
     * Example : A + 2 = C  Or  Z + 1 = AA  OR  AY + 2 = BA
     * @param $letter1
     * @param $number
     * @return string
     */
    protected function SUMOfLetter($letter1, $number)
    {
        $ascii = ord($letter1) + $number;
        $prefix = '';
        if ($letter1 == 'AA' || $this->moreThanZ) {
            $prefix = 'A';
            $this->moreThanZ = true;
        }

        if ($ascii > 90)
        {
            $prefix = 'A';
            $ascii -= 26;
            while ($ascii > 90)
            {
                $prefix++;
                $ascii -= 90;
            }
        }
        return $prefix . chr($ascii);
    }
}