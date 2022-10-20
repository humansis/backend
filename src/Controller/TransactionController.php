<?php

declare(strict_types=1);

namespace Controller;

use Exception;
use Pagination\Paginator;
use Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\TransactionFilterInputType;
use Psr\SimpleCache\InvalidArgumentException;
use Services\CodeListService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Entity\Transaction;
use Repository\TransactionRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Utils\TransactionService;

class TransactionController extends AbstractController
{
    /** @var CodeListService */
    private $codeListService;

    /** @var LoggerInterface */
    private $logger;

    /** @var TransactionService */
    private $transactionService;

    /** @var SerializerInterface */
    private $serializer;

    public function __construct(
        CodeListService $codeListService,
        LoggerInterface $logger,
        TransactionService $transactionService,
        SerializerInterface $serializer
    ) {
        $this->codeListService = $codeListService;
        $this->logger = $logger;
        $this->transactionService = $transactionService;
        $this->serializer = $serializer;
    }

    /**
     * @Rest\Get("/web-app/v1/transactions")
     *
     * @param Request $request
     * @param TransactionFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function list(Request $request, TransactionFilterInputType $filter): JsonResponse
    {
        /** @var TransactionRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Transaction::class);
        $data = $repository->findByParams($filter);

        return $this->json($data);
    }

    /**
     * @Rest\Post("/web-app/v1/assistances/{id}/transactions")
     *
     * @param Assistance $assistance
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws InvalidArgumentException
     * @deprecated Probably does not work. Do not try to use transaction before you refactor them, please.
     *
     */
    public function createTransactions(Assistance $assistance, Request $request): Response
    {
        $request->request->set('__country', $request->headers->get('country'));

        $countryISO3 = $request->request->get('__country');
        $code = $request->request->get('code');
        $user = $this->getUser();

        $this->logger->error('Sending money requested', [$countryISO3, $user, $assistance]);

        $code = (int) trim(preg_replace('/\s+/', ' ', $code));

        $validatedTransaction = $this->transactionService->verifyCode($code, $user, $assistance);
        if (!$validatedTransaction) {
            $this->logger->warning('Code: did not match');

            return new Response(
                "The supplied code did not match. The transaction cannot be executed",
                Response::HTTP_BAD_REQUEST
            );
        } else {
            $this->logger->error('Code: verified');
        }

        try {
            $response = $this->transactionService->sendMoney($countryISO3, $assistance, $user);
        } catch (Exception $exception) {
            $this->logger->error('Sending money failed: ' . $exception->getMessage());

            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->serializer->serialize($response, 'json', ['groups' => ["ValidatedAssistance"], 'datetime_format' => 'd-m-Y H:m:i']);

        return new Response($json);
    }

    /**
     * @Rest\Post("/web-app/v1/assistances/{id}/transactions/emails")
     * @ParamConverter("assistance", options={"mapping": {"id": "id"}})
     *
     * @param Assistance $assistance
     *
     * @return JsonResponse
     */
    public function sendEmail(Assistance $assistance): JsonResponse
    {
        $this->transactionService->sendVerifyEmail($this->getUser(), $assistance);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Get("/web-app/v1/transactions/statuses")
     *
     * @return JsonResponse
     */
    public function statuses(): JsonResponse
    {
        $data = $this->codeListService->mapArray(Transaction::statuses());

        return $this->json(new Paginator($data));
    }
}
