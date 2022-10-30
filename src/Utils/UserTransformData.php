<?php

declare(strict_types=1);

namespace Utils;

class UserTransformData
{
    /**
     * Returns an array representation of users in order to prepare the export
     *
     * @param $users
     *
     * @return array
     */
    public function transformData($users): array
    {
        $exportableTable = [];

        foreach ($users as $user) {
            $exportableTable [] = [
                'email' => $user->getEmail(),
                'role' => $user->getRoles()[0],
            ];
        }

        return $exportableTable;
    }
}
