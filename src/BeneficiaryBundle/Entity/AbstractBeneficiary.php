<?php
namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * @ORM\Entity()
 * @ORM\Table(name="abstract_beneficiary")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="bnf_type", type="string")
 * @ORM\DiscriminatorMap({
 *     "bnf" = "Beneficiary",
 *     "hh" = "Household",
 *     "inst" = "Institution",
 *     "comm" = "Community"
 * })
*/
abstract class AbstractBeneficiary
{
    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedDistribution", "FullProject", "FullBeneficiary", "SmartcardOverview", "FullSmartcard"})
     * @SymfonyGroups({"SmartcardOverview", "FullSmartcard"})
     */
    protected $id;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

}
