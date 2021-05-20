<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ImportUpdateStatusInputType implements InputTypeInterface
{
    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotNull
     * @Assert\Choice(callback={"NewApiBundle\Enum\ImportState", "values"})
     */
    private $status;

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}
