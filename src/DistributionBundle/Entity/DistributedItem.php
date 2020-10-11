<?php

namespace DistributionBundle\Entity;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\ORM\Mapping as ORM;

/**
 * Read only entity.
 *
 * @ORM\MappedSuperclass()
 */
class DistributedItem implements \JsonSerializable
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_distribution", type="date")
     */
    private $dateDistribution;

    /**
     * @var int
     *
     * @ORM\Column(name="target_type", type="integer")
     */
    private $type;

    /**
     * @var Commodity[]
     *
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\Commodity", mappedBy="distributionData")
     */
    private $commodities;

    /**
     * @var Beneficiary
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Beneficiary")
     */
    private $beneficiary;

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $commoditiesJson = [];
        foreach ($this->commodities as $commodity) {
            $commoditiesJson[] = [
                'modality_type' => [
                    'name' => $commodity->getModalityType()->getName(),
                ],
                'unit' => $commodity->getUnit(),
                'value' => $commodity->getValue(),
                'description' => $commodity->getDescription(),
            ];
        }

        return [
            'id' => $this->id,
            'beneficiary' => [
                'id' => $this->beneficiary->getId(),
                'name' => $this->beneficiary->getLocalGivenName().' '.$this->beneficiary->getLocalFamilyName(),
            ],
            'name' => $this->name,
            'date_distribution' => $this->dateDistribution,
            'type' => $this->type,
            'commodities' => $commoditiesJson,
        ];
    }
}
