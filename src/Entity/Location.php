<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CountryDependent;
use Entity\Helper\NestedTreeTrait;
use Entity\Helper\TreeInterface;
use Enum\EnumTrait;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Location
 *
 * @ORM\Table(name="location", indexes={
 *      @ORM\Index(name="search_name", columns={"name"}),
 *      @ORM\Index(name="search_country_name", columns={"iso3", "name"}),
 *      @ORM\Index(name="search_subtree", columns={"iso3", "nested_tree_level", "nested_tree_left", "nested_tree_right"}),
 *      @ORM\Index(name="search_superpath", columns={"nested_tree_level", "nested_tree_left", "nested_tree_right"}),
 *      @ORM\Index(name="search_level", columns={"iso3", "nested_tree_level"}),
 *     })
 * @ORM\Entity(repositoryClass="Repository\LocationRepository")
 */
class Location implements TreeInterface
{
    use NestedTreeTrait;
    use CountryDependent;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SymfonyGroups({"FullBeneficiary", "FullHousehold", "SmallHousehold", "FullAssistance", "SmallAssistance", "FullVendor"})
     */
    private $id;

    /**
     * @var Location|null
     *
     * @ORM\ManyToOne(targetEntity="Entity\Location", inversedBy="childLocations")
     * @ORM\JoinColumn(name="parent_location_id", nullable=true)
     */
    private $parentLocation;

    /**
     * @var Location[]
     *
     * @ORM\OneToMany(targetEntity="Entity\Location", mappedBy="parentLocation")
     */
    private $childLocations;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="enum_normalized_name", type="string", length=255, nullable=false, unique=true)
     */
    private $enumNormalizedName;

    /**
     * @param string $countryIso3
     * @param string|null $name
     * @param string|null $code
     */
    public function __construct(
        string $countryIso3,
        ?string $name = null,
        ?string $code = null
    ) {
        $this->setCountryIso3($countryIso3);
        $this->name = $name;
        $this->code = $code;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Location|null
     */
    public function getParentLocation(): ?Location
    {
        return $this->parentLocation;
    }

    /**
     * @param Location|null $parentLocation
     */
    public function setParentLocation(?Location $parentLocation): void
    {
        $this->parentLocation = $parentLocation;
    }

    /**
     * @return Location[]
     */
    public function getChildLocations(): iterable
    {
        return $this->childLocations;
    }

    /**
     * @param Location[] $childLocations
     */
    public function setChildLocations(array $childLocations): void
    {
        $this->childLocations = $childLocations;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
        $this->enumNormalizedName = EnumTrait::normalizeValue($name);
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getLocationByLevel(int $level): ?Location
    {
        if ($level > $this->getLvl()) {
            return null;
        }

        if ($level === $this->getLvl()) {
            return $this;
        }

        $location = $this;
        while ($level < $location->getLvl()) {
            $location = $location->getParent();
        }

        return $location;
    }

    public function getParent(): ?TreeInterface
    {
        return $this->getParentLocation();
    }

    public function getChildren(): iterable
    {
        return $this->getChildLocations();
    }

    /**
     * @return string
     */
    public function getEnumNormalizedName(): string
    {
        return $this->enumNormalizedName;
    }

    /**
     * @param string $separator
     * @return string
     */
    public function getFullPathNames(string $separator = ', '): string
    {
        $names = [];

        $location = $this;
        $names[] = $location->getName();

        while ($location->getParent() !== null) {
            $location = $location->getParent();
            $names[] = $location->getName();
        }

        return implode($separator, array_reverse($names));
    }

    //region backward compatibility
    public function getAdm1Name(): string
    {
        return $this->getAdmName(1);
    }

    public function getAdm2Name(): string
    {
        return $this->getAdmName(2);
    }

    public function getAdm3Name(): string
    {
        return $this->getAdmName(3);
    }

    public function getAdm4Name(): string
    {
        return $this->getAdmName(4);
    }

    public function getAdm1Id(): ?int
    {
        return $this->getAdmId(1);
    }

    public function getAdm2Id(): ?int
    {
        return $this->getAdmId(2);
    }

    public function getAdm3Id(): ?int
    {
        return $this->getAdmId(3);
    }

    public function getAdm4Id(): ?int
    {
        return $this->getAdmId(4);
    }

    private function getAdmName(int $level): string
    {
        $location = $this->getLocationByLevel($level);

        return $location
            ? $location->getName()
            : '';
    }

    private function getAdmId(int $level): ?int
    {
        $location = $this->getLocationByLevel($level);

        return $location
            ? $location->getId()
            : null;
    }
    //endregion
}
