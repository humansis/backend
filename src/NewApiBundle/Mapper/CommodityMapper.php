<?php


namespace NewApiBundle\Mapper;


use NewApiBundle\Entity\Commodity;
use NewApiBundle\Exception\InvalidTypeException;

class CommodityMapper
{
    public function toModalityIcon(Commodity $commodity): string
    {
        return $commodity->getModalityType()->getName(); //TODO handling entities in new endpoints
    }


    /**
     * @param iterable $commodities
     *
     * @return iterable
     *
     * @throws InvalidTypeException
     */
    public function toModalityIcons(iterable $commodities): iterable
    {
        foreach ($commodities as $commodity) {
            if ( ($commodity instanceof Commodity) ) {
                throw new InvalidTypeException(Commodity::class);
            }

            yield $this->toModalityIcon($commodity);
        }
    }
}