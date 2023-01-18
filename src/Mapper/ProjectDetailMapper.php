<?php

declare(strict_types=1);

namespace Mapper;

use Entity\Project;
use Psr\Cache\InvalidArgumentException;

class ProjectDetailMapper extends ProjectMapper
{
    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return
            $object instanceof Project &&
            isset($context[self::NEW_API]) &&
            true === $context[self::NEW_API] &&
            true === array_key_exists('detail', $context) &&
            true === $context['detail'];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getAssistanceCount(): int
    {
        return $this->projectService->getAssistanceCountByProject($this->object);
    }
}
