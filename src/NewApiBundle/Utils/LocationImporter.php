<?php

declare(strict_types=1);

namespace NewApiBundle\Utils;

use NewApiBundle\Entity\Location;
use NewApiBundle\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class LocationImporter
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var string path */
    private $file;

    /** @var int|null */
    private $limit;

    /** @var string */
    private $iso3;

    /** @var int */
    private $importedLocations = 0;

    /** @var int */
    private $omittedLocations = 0;

    /**
     * @var LocationRepository
     */
    private $locationRepository;

    /**
     * LocationService constructor.
     *
     * @param ObjectManager      $entityManager
     * @param string             $file
     * @param LocationRepository $locationRepository
     */
    public function __construct(ObjectManager $entityManager, string $file, LocationRepository $locationRepository)
    {
        $this->em = $entityManager;
        $this->file = $file;
        $this->locationRepository = $locationRepository;
        $this->iso3 = strtoupper(pathinfo($this->file, PATHINFO_FILENAME));
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @param int|null $limit
     */
    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    public function getCount(): int
    {
        $xml = new \SimpleXMLElement(file_get_contents($this->file));
        return count($xml->xpath('//*'));
    }

    /**
     * @return string
     */
    public function getIso3(): string
    {
        return $this->iso3;
    }

    /**
     * @return iterable
     * @throws \Doctrine\DBAL\Exception
     */
    public function importLocations(): iterable
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $xml = new \XMLReader();
        if (false === $xml->open($this->file)) {
            throw new FileNotFoundException('File '.$this->file.' does not exists.');
        }

        $i = 0;
        $adm1 = $adm2 = $adm3 = $adm4 = null;
        while ($xml->read()) {
            if (\XMLReader::END_ELEMENT === $xml->nodeType && 'adm1' === $xml->name) {
                $this->em->flush();
                $this->em->clear();
                continue;
            }

            if (\XMLReader::ELEMENT !== $xml->nodeType) {
                continue;
            }

            $name = $xml->getAttribute('name');
            $code = $xml->getAttribute('code');

            $adm = null;
            if ('adm1' === $xml->name) {
                $adm = $adm1 = $this->buildLocation($name, $code, $this->iso3, 1);
            } elseif ('adm2' === $xml->name) {
                $adm = $adm2 = $this->buildLocation($name, $code, $this->iso3, 2, $adm1);
            } elseif ('adm3' === $xml->name) {
                $adm = $adm3 = $this->buildLocation($name, $code, $this->iso3, 3, $adm2);
            } elseif ('adm4' === $xml->name) {
                $adm = $this->buildLocation($name, $code, $this->iso3, 4, $adm3);
            }
            if ($adm && $adm->getName() != $name) {
                yield [
                    'inconsistent' => $adm,
                    'old' => $adm->getName(),
                    'new' => $name,
                ];
            } elseif ($adm && null === $adm->getId()) {
                yield [
                    'imported' => $adm,
                ];
            } else {
                yield [
                    'omitted' => $adm,
                ];
            }

            if (null !== $this->limit && ++$i > $this->limit) {
                break;
            }
        }

        $this->em->flush();
        $this->em->clear();

        $xml->close();

        $this->file = null;

        $this->em->getConnection()->executeQuery("CALL recalculateLocationNestedSet;");
    }

    private function buildLocation(string $name, string $code, string $iso3, int $level, ?Location $parentLocation = null): Location
    {
        $locations = $this->locationRepository->findLocationsByCode($code, $iso3);

        if (count($locations) > 1) {
            $location = $locations[0];
            $this->omittedLocations++;
        } elseif (isset($locations[0])) {
            $location = $locations[0];
            $location->setCountryISO3($iso3);
            $location->setName($name);
            $location->setCode($code);
            $location->setParentLocation($parentLocation);
            $location->setLvl($level);

            $this->em->persist($location);
            $this->importedLocations++;
        } elseif (!isset($locations[0])) {
            $location = new Location($iso3);
            $location->setName($name);
            $location->setCode($code);
            $location->setParentLocation($parentLocation);
            $location->setLvl($level);

            $this->em->persist($location);
            $this->importedLocations++;
        } else {
            throw new \Exception("Unknown problem with searching locations");
        }

        return $location;
    }

    /**
     * @return int
     */
    public function getImportedLocations(): int
    {
        return $this->importedLocations;
    }

    /**
     * @return int
     */
    public function getOmittedLocations(): int
    {
        return $this->omittedLocations;
    }

}
