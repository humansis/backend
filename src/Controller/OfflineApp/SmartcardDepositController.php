<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Smartcard\Deposit\DepositFactory;
use Component\Smartcard\Deposit\Exception\DoubledDepositException;
use Enum\ReliefPackageState;
use InputType\Smartcard\DepositInputType;
use InputType\SmartcardDepositFilterInputType;
use Repository\Assistance\ReliefPackageRepository;
use Repository\AssistanceBeneficiaryRepository;
use Psr\Cache\InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Entity\SmartcardDeposit;
use Repository\SmartcardDepositRepository;

class SmartcardDepositController extends AbstractOfflineAppController
{
    public function __construct(private readonly string $logsDir, private readonly ManagerRegistry $managerRegistry)
    {
    }

    /**
     * @Rest\Get("/offline-app/v1/smartcard-deposits")
     *
     *
     */
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

    /**
     * @Rest\Get("/offline-app/v1/last-smartcard-deposit/{id}")
     *
     *
     */
    public function lastSmartcardDeposit(SmartcardDeposit $smartcardDeposit, Request $request): JsonResponse
    {
        $response = $this->json($smartcardDeposit);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * Put money to smartcard. If smartcard does not exist, it will be created.
     *
     * @Rest\Post("/offline-app/v4/smartcards/{serialNumber}/deposit")
     * @param DepositInputType $depositInputType
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws InvalidArgumentException
     * @throws DoubledDepositException
     * @deprecated Use /offline-app/v5/smartcards/{serialNumber}/deposit instead (version with Relief package)
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE') or is_granted('ROLE_FIELD_OFFICER') or is_granted('ROLE_ENUMERATOR')")
     *
     */
    public function depositLegacy(
        string $serialNumber,
        Request $request,
        DepositFactory $depositFactory,
        ReliefPackageRepository $reliefPackageRepository,
        AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository
    ): Response {
        $depositInputType = new DepositInputType();
        $assistanceId = $request->request->getInt('assistanceId');
        $beneficiaryId = $request->request->getInt('beneficiaryId');

        try {
            $assistanceBeneficiary = $assistanceBeneficiaryRepository->findByAssistanceAndBeneficiary(
                $assistanceId,
                $beneficiaryId
            );
            if (null == $assistanceBeneficiary) {
                throw new NotFoundHttpException("No beneficiary #$beneficiaryId in assistance #$assistanceId");
            }

            // try to find relief package with correct state
            $reliefPackage = $reliefPackageRepository->findForSmartcardByAssistanceBeneficiary(
                $assistanceBeneficiary,
                ReliefPackageState::TO_DISTRIBUTE
            );
            if (!$reliefPackage) {  // try to find relief package with incorrect state but created before distribution date
                $reliefPackage = $reliefPackageRepository->findForSmartcardByAssistanceBeneficiary(
                    $assistanceBeneficiary,
                    null,
                    $depositInputType->getCreatedAt()
                );
            }
            if (!$reliefPackage) {  // try to find any relief package for distribution
                $reliefPackage = $reliefPackageRepository->findForSmartcardByAssistanceBeneficiary(
                    $assistanceBeneficiary
                );
            }

            if (!$reliefPackage) {
                $message = "Nothing to distribute for beneficiary #{$assistanceBeneficiary->getBeneficiary()->getId()} in assistance #{$assistanceBeneficiary->getAssistance()->getId()}";
                throw new NotFoundHttpException($message);
            }

            $depositInputType->setCreatedAt($request->request->get('createdAt'));
            $depositInputType->setValue($request->request->get('value'));
            $depositInputType->setBalance($request->request->get('balanceBefore'));
            $depositInputType->setReliefPackageId($reliefPackage->getId());
            $deposit = $depositFactory->create($serialNumber, $depositInputType, $this->getUser());
        } catch (DoubledDepositException $exception) {
            return new Response('', Response::HTTP_ACCEPTED);
        } catch (NotFoundHttpException $exception) {
            $this->writeData(
                'depositV4',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all(), JSON_THROW_ON_ERROR)
            );

            // due to PIN-2943 was removed exception propagation
            return new Response();
        } catch (Exception $exception) {
            $this->writeData(
                'depositV4',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all(), JSON_THROW_ON_ERROR)
            );
            throw $exception;
        }

        return $this->json($deposit->getSmartcard());
    }

    /**
     * @Rest\Post("/offline-app/v5/smartcards/{serialNumber}/deposit")
     *
     *
     * @throws DoubledDepositException
     * @throws InvalidArgumentException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deposit(
        Request $request,
        string $serialNumber,
        DepositInputType $depositInputType,
        DepositFactory $depositFactory
    ): Response {
        try {
            $depositFactory->create($serialNumber, $depositInputType, $this->getUser());
        } catch (NotFoundHttpException $e) {
            $this->writeData(
                'depositV5',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all(), JSON_THROW_ON_ERROR)
            );
        } catch (DoubledDepositException $e) {
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
