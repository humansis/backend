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
     *
     * @ORM\Column(name="nested_tree_level", type="integer", nullable=true)
     */
    protected $lvl;

    /**
     * @var int
     *
     * @ORM\Column(name="nested_tree_left", type="integer", nullable=true)
     */
    protected $lft;

    /**
     * @var int
     *
     * @ORM\Column(name="nested_tree_right", type="integer", nullable=true)
     */
    protected $rgt;

    /**
     * @return int
     */
    public function getLvl(): int
    {
        return $this->lvl;
    }

    /**
     * @param int $lvl
     */
    public function setLvl(int $lvl): void
    {
        $this->lvl = $lvl;
    }

    /**
     * @return int
     */
    public function getLft(): int
    {
        return $this->lft;
    }

    /**
     * @param int $lft
     */
    public function setLft(int $lft): void
    {
        $this->lft = $lft;
    }

    /**
     * @return int
     */
    public function getRgt(): int
    {
        return $this->rgt;
    }

    /**
     * @param int $rgt
     */
    public function setRgt(int $rgt): void
    {
        $this->rgt = $rgt;
    }
}
