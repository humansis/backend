<?php

declare(strict_types=1);

namespace InputType;

use InputType\FilterFragment\AssistanceStateFilterTrait;
use InputType\FilterFragment\FulltextFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;

class ProjectsAssistanceFilterInputType extends AbstractFilterInputType
{
    use AssistanceStateFilterTrait;
    use FulltextFilterTrait;
}
