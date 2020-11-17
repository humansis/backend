<?php
declare(strict_types=1);

namespace NewApiBundle\Serializer;

interface OutputTypeInterface
{
    /**
     *
     * @param object $object
     *
     * @return bool
     */
    public function supports(object $object): bool;

    /**
     *
     * @param object $object
     *
     */
    public function populate(object $object);
}
