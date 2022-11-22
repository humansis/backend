<?php

namespace Services;

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
