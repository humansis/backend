<?php

declare(strict_types=1);

namespace InputType;

use InputType\FilterFragment\FulltextFilterTrait;
use InputType\FilterFragment\PrimaryIdFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;
use Entity\Booklet;

#[Assert\GroupSequence(['BookletFilterInputType', 'Strict'])]
class BookletFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
    use FulltextFilterTrait;

    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback="bookletStatuses", strict=true)
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
    protected $statuses;

    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("string", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
    protected $currencies;

    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
    protected $distributions;

    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
    protected $beneficiaries;

    public static function bookletStatuses()
    {
        return array_keys(Booklet::statuses());
    }

    public function hasStatuses(): bool
    {
        return $this->has('statuses');
    }

    public function getStatuses(): array
    {
        return $this->statuses;
    }

    public function hasCurrencies(): bool
    {
        return $this->has('currencies');
    }

    public function getCurrencies(): array
    {
        return $this->currencies;
    }

    public function hasDistributions(): bool
    {
        return $this->has('distributions');
    }

    public function getDistributions(): array
    {
        return $this->distributions;
    }

    public function hasBeneficiaries(): bool
    {
        return $this->has('beneficiaries');
    }

    public function getBeneficiaries(): array
    {
        return $this->beneficiaries;
    }
}
