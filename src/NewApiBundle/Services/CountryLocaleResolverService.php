<?php

namespace NewApiBundle\Services;

use CommonBundle\Utils\Exception\ExportNoDataException;
use CommonBundle\Utils\ExportService;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use SimpleXMLElement;
use Symfony\Component\Finder\Finder;
use UnexpectedValueException;

class CountryLocaleResolverService
{

    /**
     * @var array
     */
    private $countries;
    
    public function __construct(array $countries)
    {
        $this->countries = [];
        foreach ($countries as $country) {
            $this->countries[$country['iso3']] = $country['language'];
        }
    }

    /**
     * @param string $countryCode
     *
     * @return string
     */
    public function resolve(string $countryCode): string
    {
        if (key_exists($countryCode, $this->countries)) {
            return $this->countries[$countryCode];
        }
        return 'en';
    }
}