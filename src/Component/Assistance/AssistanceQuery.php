<?php declare(strict_types=1);

namespace Component\Assistance;

use Repository\AssistanceRepository;
use Component\Assistance\Domain\Assistance;

class AssistanceQuery
{
    /** @var AssistanceRepository */
    private $rootRepository;
    /** @var AssistanceFactory */
    private $factory;

    /**
     * @param AssistanceRepository $rootRepository
     * @param AssistanceFactory    $factory
     */
    public function __construct(AssistanceRepository $rootRepository, AssistanceFactory $factory)
    {
        $this->rootRepository = $rootRepository;
        $this->factory = $factory;
    }

    public function find(int $assistanceRootId): Assistance
    {
        $assistanceRoot = $this->rootRepository->find($assistanceRootId);
        return $this->factory->hydrate($assistanceRoot);
    }
}
