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

    const REPOSITORY_API_URL = 'https://gitlab-public.quanti.cz/api/v4/projects/33/repository/tree?path=locations';
    const REPOSITORY_URL = 'https://gitlab-public.quanti.cz/humansis/web-platform/administrative-areas/-/raw/master/locations/';


    public function __construct(private readonly string $repositoryApiUrl, private readonly string $repositoryUrl)
    {
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
            $admFiles[$fileNameWithoutExtension] = $this->repositoryUrl. $file['name'];
        }
        if (count($admFiles) === 0) {
            throw new \Exception("Getting XML files failed. Please check if there are any files at {$this->repositoryApiUrl}");
        }
        return $admFiles;
    }
}
