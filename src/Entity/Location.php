<?php

namespace Entity;

use Doctrine\Common\Collections\Collection;
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
 *      @ORM\Index(name="duplicity", columns={"iso3", "nested_tree_level", "enum_normalized_name"}),
 *     })
 * @ORM\Entity(repositoryClass="Repository\LocationRepository")
 */
// TODO add unique on normalized name X parent location:
// uniqueConstraints={ @ORM\UniqueConstraint(name="name_parent_unique", columns={"enum_normalized_name", "parent_location_id"}) })
// (now resolves in SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'XXX' for key 'location.name_parent_unique')
class Location implements TreeInterface
{
    use NestedTreeTrait;
    use CountryDependent;

    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    #[SymfonyGroups(['FullBeneficiary', 'FullHousehold', 'SmallHousehold', 'FullAssistance', 'SmallAssistance', 'FullVendor'])]
    private int $id;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Entity\Location", inversedBy="childLocations")
     * @ORM\JoinColumn(name="parent_location_id", nullable=true)
     */
    private ?\Entity\Location $parentLocation = null;

    /**
     * @var Collection|null
     *
     * @ORM\OneToMany(targetEntity="Entity\Location", mappedBy="parentLocation")
     */
    private ?Collection $childLocations = null;

    /**
     * @ORM\Column(name="enum_normalized_name", type="string", length=255, nullable=false)
     */
    private ?string $enumNormalizedName = null;

    /**
     * @ORM\Column(name="duplicity_count", type="integer", nullable=false)
     */
    private int $duplicityCount = 0;

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

    public function __construct(
        string $countryIso3
    ) {
        $this->setCountryIso3($countryIso3);
    }

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getParentLocation(): ?Location
    {
        return $this->parentLocation;
    }

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

    public function getName(): string
    {
        return $this->name;
    }

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

    public function getEnumNormalizedName(): string
    {
        return $this->enumNormalizedName;
    }

    public function getDuplicityCount(): int
    {
        return $this->duplicityCount;
    }

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
