<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Entity;
use NewApiBundle\Enum\ImportState;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;

class ImportCliController extends AbstractController
{
    /**
     * for testing purposes ONLY, it must be removed in 2021
     * @Rest\Get("/imports/cli/{id}")
     *
     * @param Entity\Import $import
     *
     * @return Response
     * @throws \Exception
     */
    public function cli(Entity\Import $import): Response
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
