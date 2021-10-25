<?php declare(strict_types=1);

namespace NewApiBundle\Workflow;

class ImportTransitions
{
    public const
        TO_INTEGRITY_CHECK = 'to_integrity_check',
        INTEGRITY_CHECK = 'integrity_check',
        IDENTITY_CHECK = 'identity_check',
        IMPORTING = 'importing';
}
