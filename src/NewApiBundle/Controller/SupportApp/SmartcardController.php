<?php

namespace NewApiBundle\Controller\SupportApp;


use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Controller\AbstractController;
use NewApiBundle\InputType\Smartcard\UpdateSmartcardInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use VoucherBundle\Repository\SmartcardRepository;
use VoucherBundle\Utils\SmartcardService;

class SmartcardController extends AbstractController
{
    /**
     * @var SmartcardRepository
     */
    private $smartcardRepository;

    public function __construct(
        SmartcardRepository $smartcardRepository
    )
    {
        $this->smartcardRepository = $smartcardRepository;
    }

    /**
     * @Rest\Get("/support-app/v1/smartcards/{smartcardCode}")
     *
     * @param string $smartcardCode
     *
     * @return JsonResponse
     */
    public function smartcard(string $smartcardCode):JsonResponse
    {
        $smartcard = $this->smartcardRepository->findOneBy(['serialNumber' => $smartcardCode]);

        if (!$smartcard){
            throw new NotFoundHttpException('Unable to find smartcard.');
        }

        return $this->json($smartcard);
    }

    /**
     * @Rest\Get ("/support-app/v1/smartcards/{smartcardCode}/purchases")
     *
     * @param string $smartcardCode
     *
     * @return JsonResponse
     */
     public function smartcardPurchases(string $smartcardCode):JsonResponse
     {
         $smartcard = $this->smartcardRepository->findOneBy(['serialNumber' => $smartcardCode]);

         if (!$smartcard){
             throw new NotFoundHttpException('Unable to find smartcard.');
         }
         $purchases = $smartcard->getPurchases();

         return $this->json($purchases);
     }

    /**
     * @Rest\Get ("/support-app/v1/smartcards/{smartcardCode}/deposits")
     *
     * @param string $smartcardCode
     *
     * @return JsonResponse
     */
    public function smartcardDeposits(string $smartcardCode):JsonResponse
    {
        $smartcard = $this->smartcardRepository->findOneBy(['serialNumber' => $smartcardCode]);

        if (!$smartcard){
            throw new NotFoundHttpException('Unable to find smartcard.');
        }
        $purchases = $smartcard->getDeposites();

        return $this->json($purchases);
    }

    /**
     * @Rest\Patch("/support-app/v1/smartcards/{serialNumber}")
     *
     * @param string                   $serialNumber
     * @param UpdateSmartcardInputType $updateSmartcardInputType
     * @param SmartcardRepository      $smartcardRepository
     * @param SmartcardService         $smartcardService
     *
     * @return JsonResponse
     */
    public function update(
        string                   $serialNumber,
        UpdateSmartcardInputType $updateSmartcardInputType,
        SmartcardService         $smartcardService ): JsonResponse {

        $user = $this->get('security.token_storage')->getToken()->getUser();

        if ($user->hasRole('ROLE_ADMIN')) {

            $smartcard = $this->smartcardRepository->findOneBy(['serialNumber' => $serialNumber]);

            if (!$smartcard) {
                throw new NotFoundHttpException('Unable to find smartcard.');
            }
            $smartcard = $smartcardService->update($smartcard, $updateSmartcardInputType);

            return $this->json($smartcard);
        }else{
            throw new AccessDeniedException('You do not have the privilege to update the Smartcard');
        }

    }
}
