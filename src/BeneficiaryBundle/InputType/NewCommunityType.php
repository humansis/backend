<?php declare(strict_types=1);

namespace BeneficiaryBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

class NewCommunityType extends UpdateCommunityType
{
    /**
     * @var int[]
     * @Assert\NotNull
     * @Assert\All(
     *     @Assert\GreaterThan(0)
     * )
     */
    public $projects;
}
