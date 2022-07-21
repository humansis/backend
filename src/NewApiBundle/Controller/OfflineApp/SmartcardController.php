<?php declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Smartcard\Exception\SmartcardDoubledRegistrationException;
use NewApiBundle\Component\Smartcard\Exception\SmartcardNotAllowedStateTransitionException;
use NewApiBundle\InputType\Smartcard\ChangeSmartcardInputType;
use NewApiBundle\InputType\Smartcard\SmartcardRegisterInputType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use VoucherBundle\Repository\SmartcardRepository;
use VoucherBundle\Utils\SmartcardService;

class SmartcardController extends AbstractOfflineAppController
{
    /**
     * @Rest\Post("/offline-app/v1/smartcards")
     *
     * @param SmartcardRegisterInputType $registerInputType
     * @param SmartcardService           $smartcardService
     *
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function register(SmartcardRegisterInputType $registerInputType, SmartcardService $smartcardService): Response
    {
        try {
            $smartcardService->register($registerInputType);

            return Response::create();
        } catch (SmartcardDoubledRegistrationException $e) {
            return Response::create('', Response::HTTP_ACCEPTED);
        }
    }

    /**
     * Update smartcard, typically its' state.
     *
     * @Rest\Patch("/offline-app/v1/smartcards/{serialNumber}")
     *
     * @param string                   $serialNumber
     * @param ChangeSmartcardInputType $changeSmartcardInputType
     * @param SmartcardRepository      $smartcardRepository
     * @param SmartcardService         $smartcardService
     *
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function change(
        string                   $serialNumber,
        ChangeSmartcardInputType $changeSmartcardInputType,
        SmartcardRepository      $smartcardRepository,
        SmartcardService         $smartcardService
    ): Response {
        $smartcard = $smartcardRepository->findBySerialNumberAndChangeParameters($serialNumber, $changeSmartcardInputType);
        if ($smartcard) {
            return Response::create('', Response::HTTP_ACCEPTED);
        }
        $smartcard = $smartcardRepository->findActiveBySerialNumber($serialNumber);
        if (!$smartcard) {
            throw $this->createNotFoundException("Smartcard with code '$serialNumber' was not found.");
        }

        try {
            $smartcardService->change($smartcard, $changeSmartcardInputType);

            return Response::create();
        } catch (SmartcardNotAllowedStateTransitionException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }
}
