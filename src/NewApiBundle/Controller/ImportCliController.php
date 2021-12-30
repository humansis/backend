<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;


use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Import\ImportFileValidator;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\Component\Import\UploadImportService;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Entity\ImportInvalidFile;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\InputType\DuplicityResolveInputType;
use NewApiBundle\InputType\ImportCreateInputType;
use NewApiBundle\InputType\ImportFilterInputType;
use NewApiBundle\InputType\ImportOrderInputType;
use NewApiBundle\InputType\ImportPatchInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;
use UserBundle\Entity\User;

class ImportCliController extends AbstractController
{
    /**
     * for testing purposes ONLY, it must be removed in 2021
     * @Rest\Get("/imports/cli/{id}")
     *
     * @param Import $import
     *
     * @return Response
     * @throws \Exception
     */
    public function cli(Import $import): Response
    {
        $kernel = $this->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $output = new BufferedOutput();
        switch ($import->getState()) {
            case ImportState::INTEGRITY_CHECKING:
                $application->run(new ArrayInput([
                    'command' => 'app:import:integrity',
                    'import' => $import->getId(),
                ]), $output);
                break;
            case ImportState::IDENTITY_CHECKING:
                $application->run(new ArrayInput([
                    'command' => 'app:import:identity',
                    'import' => $import->getId(),
                ]), $output);
                break;
            case ImportState::SIMILARITY_CHECKING:
                $application->run(new ArrayInput([
                    'command' => 'app:import:similarity',
                    'import' => $import->getId(),
                ]), $output);
                break;
            case ImportState::IMPORTING:
                $application->run(new ArrayInput([
                    'command' => 'app:import:finish',
                    'import' => $import->getId(),
                ]), $output);
                break;
        }

        return new Response($output->fetch());
    }

}
