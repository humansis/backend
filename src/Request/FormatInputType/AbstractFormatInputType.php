<?php

declare(strict_types=1);

namespace Request\FormatInputType;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractFormatInputType implements FormatInputTypeInterface
{
    private string $type;

    /**
     * @return array list of accepted Format names
     */
    abstract protected function getValidNames(): array;

    public function setType(string $type)
    {
        $validNames = $this->getValidNames();

        if (!in_array($type, $validNames)) {
            throw new BadRequestHttpException('This format "type" is not supported for data export');
        }
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
