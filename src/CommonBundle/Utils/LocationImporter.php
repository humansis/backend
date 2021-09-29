<?php

declare(strict_types=1);

namespace CommonBundle\Utils;

use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
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

    /** @var int */
    private $importedLocations = 0;

    /** @var int */
    private $omittedLocations = 0;

    /**
     * LocationService constructor.
     *
     * @param ObjectManager $entityManager
     * @param string        $file
     */
    public function __construct(ObjectManager $entityManager, string $file)
    {
        $this->em = $entityManager;
        $this->file = $file;
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
     * @return iterable
     */
    public function importLocations(): iterable
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $iso3 = strtoupper(pathinfo($this->file, PATHINFO_FILENAME));

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
                $adm = $adm1 = $this->buildAdm1($name, $code, $iso3);
            } elseif ('adm2' === $xml->name) {
                $adm = $adm2 = $this->buildAdm2($name, $code, $adm1);
            } elseif ('adm3' === $xml->name) {
                $adm = $adm3 = $this->buildAdm3($name, $code, $adm2);
            } elseif ('adm4' === $xml->name) {
                $adm = $this->buildAdm4($name, $code, $adm3);
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
    }

    private function buildAdm1(string $name, string $code, string $iso3): Adm1
    {
        $adm1 = $this->em->getRepository(Adm1::class)->findOneByCode($code);
        if (!$adm1) {
            $adm1 = (new Adm1())
                ->setCountryISO3($iso3)
                ->setName($name)
                ->setCode($code);

            $this->em->persist($adm1);
            $this->importedLocations++;
        } else {
            $this->omittedLocations++;
        }

        return $adm1;
    }

    private function buildAdm2(string $name, string $code, Adm1 $adm1): Adm2
    {
        $adm2 = $this->em->getRepository(Adm2::class)->findOneByCode($code);
        if (!$adm2) {
            $adm2 = (new Adm2())
                ->setAdm1($adm1)
                ->setName($name)
                ->setCode($code);

            $this->em->persist($adm2);
            $this->importedLocations++;
        } else {
            $this->omittedLocations++;
        }

        return $adm2;
    }

    private function buildAdm3(string $name, string $code, Adm2 $adm2): Adm3
    {
        $adm3 = $this->em->getRepository(Adm3::class)->findOneByCode($code);
        if (!$adm3) {
            $adm3 = (new Adm3())
                ->setAdm2($adm2)
                ->setName($name)
                ->setCode($code);

            $this->em->persist($adm3);
            $this->importedLocations++;
        } else {
            $this->omittedLocations++;
        }

        return $adm3;
    }

    private function buildAdm4(string $name, string $code, Adm3 $adm3): Adm4
    {
        $adm4 = $this->em->getRepository(Adm4::class)->findOneByCode($code);
        if (!$adm4) {
            $adm4 = (new Adm4())
                ->setAdm3($adm3)
                ->setName($name)
                ->setCode($code);

            $this->em->persist($adm4);
            $this->importedLocations++;
        } else {
            $this->omittedLocations++;
        }

        return $adm4;
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
