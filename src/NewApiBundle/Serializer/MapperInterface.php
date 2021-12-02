<?php
declare(strict_types=1);

namespace NewApiBundle\Serializer;

interface MapperInterface
{
    const
        NEW_API = 'new_api',
        OFFLINE_APP = 'offline-app',
        VENDOR_APP = 'vendor-app';

    /**
     * @param object     $object
     * @param null       $format
     * @param array|null $context
     *
     * @return bool
     */
    public function supports(object $object, $format = null, array $context = null): bool;

    /**
     *
     * @param object $object
     *
     */
    public function populate(object $object);
}
