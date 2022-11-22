<?php

declare(strict_types=1);

namespace Workflow;

final class ReliefPackageTransitions
{
    public const START_PARTIAL_DISTRIBUTION = 'Start partial distribution';
    public const FINISH_PARTIAL_DISTRIBUTION = 'Finish partial distribution';
    public const DISTRIBUTE = 'Distribute everything';
    public const EXPIRE = 'Expire';
    public const CANCEL = 'Cancel';
}
