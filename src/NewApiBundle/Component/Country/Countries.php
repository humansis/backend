<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Country;

class Countries
{
    private $array;

    public function __construct(array $array)
    {
        $this->array = $array;
    }

    /**
     * @return Country[]
     */
    public function getAll(): array
    {
        return $this->lazyList();
    }

    /**
     * @param string $iso3
     *
     * @return Country|null
     */
    public function getCountry(string $iso3): ?Country
    {
        foreach ($this->lazyList() as $country) {
            if ($iso3 === $country->getIso3()) {
                return $country;
            }
        }

        return null;
    }

    public function hasCountry(string $iso3): bool
    {
        foreach ($this->lazyList() as $country) {
            if ($iso3 === $country->getIso3()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Country[]
     */
    private function lazyList()
    {
        static $cache;

        if (null === $cache) {
            $cache = [];
            foreach ($this->array as $item) {
                $cache[] = new Country($item);
            }
        }

        return $cache;
    }
}
