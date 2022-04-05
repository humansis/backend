<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Validator\Constraints;

use NewApiBundle\Validator\Constraints\Country;
use NewApiBundle\Validator\Constraints\ImportDate;
use NewApiBundle\Validator\Constraints\Iso8601;

class TestDummyObject
{
    /**
     * @Iso8601()
     * @var string
     */
    private $isoDate;

    /**
     * @ImportDate()
     * @var string
     */
    private $importDate;

    /**
     * @Country()
     * @var string
     */
    private $countryISO3;

    /**
     * @param string|null $isoDate
     * @param string|null $importDate
     * @param string|null $countryISO3
     */
    public function __construct(?string $isoDate, ?string $importDate, ?string $countryISO3)
    {
        $this->isoDate = $isoDate;
        $this->importDate = $importDate;
        $this->countryISO3 = $countryISO3;
    }

    /**
     * @return string
     */
    public function getIsoDate(): string
    {
        return $this->isoDate;
    }

    /**
     * @return string
     */
    public function getImportDate(): string
    {
        return $this->importDate;
    }

    /**
     * @return string
     */
    public function getCountryISO3(): string
    {
        return $this->countryISO3;
    }

}
