<?php declare(strict_types=1);

namespace NewApiBundle\Component\Http;

use NewApiBundle\Component\Country\Countries;
use Symfony\Component\HttpFoundation\RequestStack;

class Request
{
    public const COUNTRY_HEADER = 'country';

    /**
     * @var Countries
     */
    private $countries;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    private $request;

    public function __construct(Countries $countries, RequestStack $request)
    {
        $this->countries = $countries;
        $this->request = $request->getCurrentRequest();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest(): \Symfony\Component\HttpFoundation\Request
    {
        return $this->request;
    }

    /**
     * @return bool
     */
    public function hasCountry(): bool
    {
        if (!$this->request->headers->has(self::COUNTRY_HEADER)) {
            throw new HeaderCountryException();
        }

        return true;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        if ($this->hasCountry()) {
            $country = $this->request->headers->get(self::COUNTRY_HEADER);
            if ($this->countries->getCountry($country)) {
                return $country;
            } else {
                throw new HeaderCountryException("$country is not supported country iso3 code");
            }
        }

        throw new HeaderCountryException();
    }
}
