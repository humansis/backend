<?php

namespace NewApiBundle\Model\Household\HouseholdChange;

use NewApiBundle\Entity\HouseholdActivity;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use UserBundle\Entity\User;

abstract class AbstractHouseholdChange
{
    private $activity;

    private $previousActivity;

    public function __construct(HouseholdActivity $activity, HouseholdActivity $previousActivity)
    {
        $this->activity = $activity;
        $this->previousActivity = $previousActivity;
    }

    /**
     * @SymfonyGroups({"HouseholdChanges"})
     */
    public function getAuthor(): ?User
    {
        return $this->activity->getAuthor();
    }

    /**
     * @SymfonyGroups({"HouseholdChanges"})
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->activity->getCreatedAt();
    }

    /**
     * @SymfonyGroups({"HouseholdChanges"})
     */
    public function getChanges(): array
    {
        $new = json_decode($this->activity->getContent(), true);
        $old = json_decode($this->previousActivity->getContent(), true);

        return $this->diff($new, $old);
    }

    final protected function diff($array1, $array2)
    {
        if (!is_array($array1) || !is_array($array2)) {
            return $array1;
        }

        $result = [];

        foreach ($array1 as $key1 => $value1) {
            if (array_key_exists($key1, $array2)) {
                if (is_array($value1)) {
                    $diff = $this->diff($value1, $array2[$key1]);
                    if ([] !== $diff) {
                        $result[$key1] = $diff;
                    }
                } else {
                    if ($value1 != $array2[$key1]) {
                        $result[$key1] = $value1;
                    }
                }
            } else {
                $result[$key1] = $value1;
            }
        }

        return $result;
    }
}
