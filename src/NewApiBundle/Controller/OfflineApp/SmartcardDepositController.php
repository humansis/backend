<?php
declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\SmartcardDepositFilterInputType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Repository\SmartcardDepositRepository;

class SmartcardDepositController extends AbstractOfflineAppController
{
    /**
     * @Rest\Get("/offline-app/v1/smartcard-deposits")
     *
     * @param Request                         $request
     * @param SmartcardDepositFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function list(Request $request, SmartcardDepositFilterInputType $filter): JsonResponse
    {
        /** @var SmartcardDepositRepository $repository */
        $repository = $this->getDoctrine()->getRepository(SmartcardDeposit::class);
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
     * @param SmartcardDeposit $smartcardDeposit
     * @param Request          $request
     *
     * @return JsonResponse
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
     * Put money to smartcard. If smartcard does not exists, it will be created.
     *
     * @Rest\Post("/offline-app/v4/smartcards/{serialNumber}/deposit")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE') or is_granted('ROLE_FIELD_OFFICER') or is_granted('ROLE_ENUMERATOR')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function deposit(Request $request): Response
    {
        try {
            $deposit = $this->get('smartcard_service')->depositLegacy(
                $request->get('serialNumber'),
                $request->request->getInt('beneficiaryId'),
                $request->request->getInt('distributionId'),
                $request->request->get('value'),
                $request->request->get('balanceBefore'),
                $request->request->get('balanceAfter'),
                \DateTime::createFromFormat('Y-m-d\TH:i:sO', $request->get('createdAt')),
                $this->getUser()
            );
        } catch (\Exception $exception) {
            $this->writeData(
                'depositV4',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all())
            );
            throw $exception;
        }

        return $this->json($deposit->getSmartcard());
    }

    private function writeData(string $type, string $user, string $smartcard, $data): void
    {
        $filename = $this->get('kernel')->getLogDir().'/';
        $filename .= implode('_', ['SC-invalidData', $type, 'vendor-'.$user, 'sc-'.$smartcard.'.json']);
        $logFile = fopen($filename, "a+");
        fwrite($logFile, $data);
        fclose($logFile);
    }
}
