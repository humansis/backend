<?php

namespace Hydration;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use PDO;

class PlainValuesHydrator extends AbstractHydrator
{
    /**
     * {@inheritdoc}
     */
    protected function hydrateAllData()
    {
        $result = [];
        foreach ($this->_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[] = reset($row);
        }

        return $result;
    }
}
