<?php

declare(strict_types=1);

namespace Entity\Helper;

use Doctrine\ORM\Mapping as ORM;

/**
 * @see TreeInterface need to be used in class with TreeInterface implementation
 */
trait NestedTreeTrait
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'nested_tree_level', type: 'integer', nullable: true)]
    protected $lvl;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nested_tree_left', type: 'integer', nullable: true)]
    protected $lft;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nested_tree_right', type: 'integer', nullable: true)]
    protected $rgt;

    public function getLvl(): int
    {
        return $this->lvl;
    }

    public function setLvl(int $lvl): void
    {
        $this->lvl = $lvl;
    }

    public function getLft(): int
    {
        return $this->lft;
    }

    public function setLft(int $lft): void
    {
        $this->lft = $lft;
    }

    public function getRgt(): int
    {
        return $this->rgt;
    }

    public function setRgt(int $rgt): void
    {
        $this->rgt = $rgt;
    }
}
