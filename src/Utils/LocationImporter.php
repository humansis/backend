<?php

declare(strict_types=1);

namespace Utils;

use Doctrine\DBAL\Exception;
use Entity\Location;
use Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use SimpleXMLElement;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use XMLReader;

class LocationImporter
{
    private ?int $limit = null;

    private readonly string $iso3;

    private int $importedLocations = 0;

    private int $omittedLocations = 0;

    /**
     * LocationService constructor.
     */
    public function __construct(private readonly ObjectManager $em, private string $file, private readonly LocationRepository $locationRepository)
    {
        $this->iso3 = strtoupper(pathinfo($this->file, PATHINFO_FILENAME));
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    public function getCount(): int
    {
        $xml = new SimpleXMLElement(file_get_contents($this->file));

        return count($xml->xpath('//*'));
    }

    public function getIso3(): string
    {
        return $this->iso3;
    }

    /**
     * @throws Exception
     */
    public function importLocations(): iterable
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $xml = new XMLReader();
        if (false === $xml->open($this->file)) {
            throw new FileNotFoundException('File ' . $this->file . ' does not exists.');
        }

        $i = 0;
        $adm1 = $adm2 = $adm3 = $adm4 = null;
        while ($xml->read()) {
            if (XMLReader::END_ELEMENT === $xml->nodeType && 'adm1' === $xml->name) {
                $this->em->flush();
                $this->em->clear();
                continue;
            }

            if (XMLReader::ELEMENT !== $xml->nodeType) {
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
            } elseif ($adm && $this->em->getUnitOfWork()->isEntityScheduled($adm)) {
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
        $this->em->getConnection()->executeQuery("CALL updateLocationDuplicity;");
    }

    private function buildLocation(
        string $name,
        string $code,
        string $iso3,
        int $level,
        ?Location $parentLocation = null
    ): Location {
        $locations = $this->locationRepository->findLocationsByCode($code, $iso3);

        if (count($locations) > 1) {
            $location = $locations[0];
            $this->omittedLocations++;
        } elseif (isset($locations[0])) {
            $location = $locations[0];
            $location->setCountryIso3($iso3);
            $location->setName($name);
            $location->setCode($code);
            $location->setParentLocation($parentLocation);
            $location->setLvl($level);

            $this->em->persist($location);
            $this->importedLocations++;
        } else {
            $location = new Location($iso3);
            $location->setName($name);
            $location->setCode($code);
            $location->setParentLocation($parentLocation);
            $location->setLvl($level);

            $this->em->persist($location);
            $this->importedLocations++;
        }

        return $location;
    }

    public function getImportedLocations(): int
    {
        return $this->importedLocations;
    }

    public function getOmittedLocations(): int
    {
        return $this->omittedLocations;
    }
}
