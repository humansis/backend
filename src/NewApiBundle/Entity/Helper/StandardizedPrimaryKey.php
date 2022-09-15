<?php
declare(strict_types=1);

namespace NewApiBundle\Entity\Helper;

use Doctrine\ORM\Mapping as ORM;

trait StandardizedPrimaryKey
{
    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedAssistance", "FullProject", "FullBeneficiary", "SmartcardOverview", "FullSmartcard"})
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

}
