<?php

declare(strict_types=1);

namespace Serializer;

interface MapperInterface
{
    public const NEW_API = 'new_api';
    public const OFFLINE_APP = 'offline-app';
    public const VENDOR_APP = 'vendor-app';
    public const WEB_API = 'web-app';
    public const SUPPORT_APP = 'support-app';

    /**
     * @param object $object
     * @param null $format
     * @param array|null $context
     *
     * @return bool
     */
    public function supports(object $object, $format = null, array $context = null): bool;

    public function populate(object $object);
}
