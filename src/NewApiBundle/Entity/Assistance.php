<?php


namespace NewApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Assistance
 *
 * @ORM\Table(name="assistance")
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\AssistanceRepository")
 */
class Assistance extends \DistributionBundle\Entity\Assistance
{
    const TYPE_TO_STRING_MAPPING = [
        self::TYPE_BENEFICIARY => 'beneficiary',
        self::TYPE_HOUSEHOLD => 'household',
    ];


    public function getTargetTypeString(): string
    {
        if (!isset(self::TYPE_TO_STRING_MAPPING[$this->getTargetType()])) {
            return '';
        }

        return self::TYPE_TO_STRING_MAPPING[$this->getTargetType()];
    }
}