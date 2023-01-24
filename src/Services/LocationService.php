<?php

declare(strict_types=1);

namespace Services;

use Doctrine\ORM\EntityManagerInterface;
use Component\Assistance\Scoring\Exception\ScoreValidationException;
use Entity\ScoringBlueprint;
use Exception\CsvParserException;
use InputType\Assistance\Scoring\ScoringService;
use InputType\ScoringInputType;
use InputType\ScoringPatchInputType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Utils\UserService;

class LocationService
{
    /**
     * @var string
     */
    private $repositoryApiUrl;

    /**
     * @var string
     */
    private $repositoryUrl;

    public function __construct(string $repositoryApiUrl, string $repositoryUrl)
    {
        $this->repositoryApiUrl = $repositoryApiUrl;
        $this->repositoryUrl = $repositoryUrl;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getADMFiles(): array
    {
        $response = file_get_contents($this->repositoryApiUrl);
        $files = json_decode($response, true);
        $admFiles = [];
        foreach ($files as $file) {
            $fileNameWithoutExtension = pathinfo($file['name'], PATHINFO_FILENAME);
            $admFiles[$fileNameWithoutExtension] = $this->repositoryUrl . $file['name'];
        }
        if (count($admFiles) === 0) {
            throw new \Exception("Getting XML files failed. Please check if there are any files at {$this->repositoryApiUrl}");
        }
        return $admFiles;
    }
}
