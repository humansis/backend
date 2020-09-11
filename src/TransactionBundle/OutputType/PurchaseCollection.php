<?php

namespace TransactionBundle\OutputType;

use Doctrine\ORM\AbstractQuery;

class PurchaseCollection implements \JsonSerializable
{
    /** @var AbstractQuery */
    private $query;

    public function __construct(AbstractQuery $query)
    {
        $this->query = $query;
    }

    public function jsonSerialize()
    {
        return $this->query->getResult();
    }
}
