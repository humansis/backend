<?php
namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * @ORM\Table(name="abstract_beneficiary")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="bnf_type", type="string")
 * @ORM\DiscriminatorMap({
 *     "bnf" = "Beneficiary"
 * })
*/
abstract class AbstractBeneficiary
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedDistribution", "FullProject", "FullBeneficiary", "SmartcardOverview", "FullSmartcard"})
     * @SymfonyGroups({"SmartcardOverview", "FullSmartcard"})
     */
    private $id;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
