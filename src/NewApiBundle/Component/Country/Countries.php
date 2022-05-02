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
    public function getAll(bool $withArchived = false): array
    {
        return $this->lazyList($withArchived);
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
    private function lazyList(bool $withArchived = false): array
    {
        static $cache;
        static $cacheArchived;

        if (null === $cache || null === $cacheArchived) {
            $cache = [];
            foreach ($this->array as $item) {
                $country = new Country($item);
                if ($country->isArchived()) {
                    $cacheArchived[] = new Country($item);
                } else {
                    $cache[] = new Country($item);
                }
            }
        }

        if ($withArchived) {
            return array_merge($cache, $cacheArchived);
        }

        return $cache;
    }
}
