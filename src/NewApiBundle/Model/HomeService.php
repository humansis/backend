<?php


namespace NewApiBundle\Model;


class HomeService
{
    public function getSummary(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'completed assistances',
                'value' => '2048'
            ]
        ];
    }
}