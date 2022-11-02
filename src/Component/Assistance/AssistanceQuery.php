<?php

declare(strict_types=1);

namespace Component\Assistance;

use Repository\AssistanceRepository;
use Component\Assistance\Domain\Assistance;

class AssistanceQuery
{
    public function __construct(private readonly AssistanceRepository $rootRepository, private readonly AssistanceFactory $factory)
    {
    }

    public function find(int $assistanceRootId): Assistance
    {
        $assistanceRoot = $this->rootRepository->find($assistanceRootId);

        return $this->factory->hydrate($assistanceRoot);
    }
}
