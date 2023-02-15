<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Smartcard\Deposit\DepositFactory;
use Component\Smartcard\Deposit\Exception\DoubledDepositException;
use InputType\Smartcard\DepositInputType;
use InputType\SmartcardDepositFilterInputType;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Entity\SmartcardDeposit;
use Repository\SmartcardDepositRepository;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class SmartcardDepositController extends AbstractOfflineAppController
{
    public function __construct(private readonly string $logsDir, private readonly ManagerRegistry $managerRegistry)
    {
    }

    #[Rest\Get('/offline-app/v1/smartcard-deposits')]
    public function list(Request $request, SmartcardDepositFilterInputType $filter): JsonResponse
    {
        /** @var SmartcardDepositRepository $repository */
        $repository = $this->managerRegistry->getRepository(SmartcardDeposit::class);
        $data = $repository->findByParams($filter);

        $response = $this->json($data);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    #[Rest\Get('/offline-app/v1/last-smartcard-deposit/{id}')]
    public function lastSmartcardDeposit(SmartcardDeposit $smartcardDeposit, Request $request): JsonResponse
    {
        $response = $this->json($smartcardDeposit);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @param Request $request
     * @param string $serialNumber
     * @param DepositInputType $depositInputType
     * @param DepositFactory $depositFactory
     * @return Response
     * @throws DoubledDepositException
     * @throws InvalidArgumentException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \JsonException
     * @throws ExceptionInterface
     */
    #[Rest\Post('/offline-app/v5/smartcards/{serialNumber}/deposit')]
    public function deposit(
        Request $request,
        string $serialNumber,
        DepositInputType $depositInputType,
        DepositFactory $depositFactory
    ): Response {
        try {
            $depositFactory->create($serialNumber, $depositInputType, $this->getUser());
        } catch (NotFoundHttpException) {
            $this->writeData(
                'depositV5',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all(), JSON_THROW_ON_ERROR)
            );
        } catch (DoubledDepositException) {
            return new Response('', Response::HTTP_ACCEPTED);
        } catch (Exception $e) {
            $this->writeData(
                'depositV5',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all(), JSON_THROW_ON_ERROR)
            );
            throw $e;
        }

        return new Response();
    }

    private function writeData(string $type, string $user, string $smartcard, $data): void
    {
        $filename = $this->logsDir . '/';
        $filename .= implode('_', ['SC-invalidData', $type, 'vendor-' . $user, 'sc-' . $smartcard . '.json']);
        $logFile = fopen($filename, "a+");
        fwrite($logFile, (string) $data);
        fclose($logFile);
    }
}
