<?php

namespace Utils\Mapper;

use Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use Enum\EnumTrait;
use Enum\Livelihood;
use Exception;

/**
 * @deprecated will be removed (at least lots of methods here) with DistributionBundle
 */
class CSVToArrayMapper
{
    private array $adms = [];

    public function __construct(protected EntityManagerInterface $em)
    {
    }

    /**
     * Makes sure the ADM are only retrieved once from the database to save database accesses
     *
     * @param array $location
     * @return mixed
     */
    private function getAdmByLocation(&$location, int $level)
    {
        $admType = 'adm' . $level;

        // Return the ADM if it has already been loaded before
        if (!empty($this->adms[$admType][$location[$admType]])) {
            return $this->adms[$admType][$location[$admType]];
        }

        $query = [
            'enumNormalizedName' => EnumTrait::normalizeValue($location[$admType]),
            'level' => $level,
            'countryIso3' => $location['country_iso3'],
        ];

        // Store the result of the query for next times
        $this->adms[$admType][$location[$admType]] = $this->em->getRepository(Location::class)->findOneBy($query);

        return $this->adms[$admType][$location[$admType]];
    }

    /**
     * Reformat the field location.
     *
     * @param $formattedHouseholdArray
     * @throws Exception
     */
    public function mapLocation(&$formattedHouseholdArray)
    {
        $location = $formattedHouseholdArray['location'];

        if ($location['adm1'] === null && $location['adm2'] === null && $location['adm3'] === null && $location['adm4'] === null) {
            if ($formattedHouseholdArray['address_street'] || $formattedHouseholdArray['camp']) {
                throw new Exception('A location is required');
            } else {
                return;
            }
        }

        if (!$location['adm1']) {
            throw new Exception('An Adm1 is required');
        }

        $lastLocationName = $location['country_iso3'];

        for ($i = 1; $i <= 4; $i++) {
            if (!$location['adm' . $i]) {
                return;
            }
            $admLocation = $this->getAdmByLocation($location, $i);
            if (!$admLocation instanceof Location) {
                throw new Exception(
                    'The Adm ' . $i . ' ' . $location['adm' . $i] . ' was not found in ' . $lastLocationName
                );
            } else {
                $formattedHouseholdArray['location']['adm' . $i] = $admLocation->getId();
                $lastLocationName = $admLocation->getName();
            }
        }
    }

    /**
     * Reformat the field livelihood.
     *
     * @param $formattedHouseholdArray
     */
    public function mapLivelihood(&$formattedHouseholdArray)
    {
        if ($formattedHouseholdArray['livelihood']) {
            $livelihood = null;
            foreach (Livelihood::values() as $value) {
                if (0 === strcasecmp(Livelihood::translate($value), (string) $formattedHouseholdArray['livelihood'])) {
                    $livelihood = $value;
                }
            }
            if ($livelihood !== null) {
                $formattedHouseholdArray['livelihood'] = $livelihood;
            } else {
                throw new Exception("Invalid livelihood.");
            }
        }
    }
}
