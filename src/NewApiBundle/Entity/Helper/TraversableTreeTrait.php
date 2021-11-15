<?php
declare(strict_types=1);

namespace NewApiBundle\Entity\Helper;

use Doctrine\ORM\Mapping as ORM;

trait TraversableTreeTrait
{
    /**
     * @var int
     *
     * @ORM\Column(name="traverse_level", type="integer", nullable=true)
     */
    protected $lvl;

    /**
     * @var int
     *
     * @ORM\Column(name="traverse_left", type="integer", nullable=true)
     */
    protected $lft;

    /**
     * @var int
     *
     * @ORM\Column(name="traverse_right", type="integer", nullable=true)
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

    public function recountTree(): void
    {

    }

    public function getSubtreeSize(): int
    {
        return 0;
    }
}
