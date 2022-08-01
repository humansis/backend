<?php

namespace TransactionBundle\Controller;

use NewApiBundle\Entity\Beneficiary;
use DistributionBundle\Entity\Assistance;
use NewApiBundle\Entity\Household;
use DistributionBundle\Mapper\AssistanceBeneficiaryMapper;
use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TransactionBundle\Entity\PurchasedItem;

/**
 * Class TransactionController
 * @package TransactionBundle\Controller
 *
 * @SWG\Parameter(
 *     name="country",
 *     in="header",
 *     type="string",
 *     required=true
 * )
 */
class TransactionController extends Controller
{

    /**
     * Send money to distribution beneficiaries via country financial provider
     * @Rest\Post("/transaction/distribution/{id}/send", name="send_money_for_distribution")
     * @ Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Transaction")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="HTTP_BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @param Assistance $assistance
     * @return Response
     */
    public function sendTransactionAction(Request $request, Assistance $assistance)
    {
        $countryISO3 = $request->request->get('__country');
        $code = $request->request->get('code');
        $user = $this->getUser();

        /** @var LoggerInterface $logger */
        $logger = $this->get('monolog.logger.mobile');
        $logger->error('Sending money requested', [$countryISO3, $user, $assistance]);

        $code = trim(preg_replace('/\s+/', ' ', $code));

        $validatedTransaction = $this->get('transaction.transaction_service')->verifyCode($code, $user, $assistance);
        if (! $validatedTransaction) {
            $logger->warning('Code: did not match');
            return new Response("The supplied code did not match. The transaction cannot be executed", Response::HTTP_BAD_REQUEST);
        } else {
            $logger->error('Code: verified');
        }
        
        try {
            $response = $this->get('transaction.transaction_service')->sendMoney($countryISO3, $assistance, $user);
        } catch (\Exception $exception) {
            $logger->error('Sending money failed: '.$exception->getMessage());
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        
        $json = $this->get('serializer')
            ->serialize($response, 'json', ['groups' => ["ValidatedAssistance"], 'datetime_format' => 'd-m-Y H:m:i']);
        return new Response($json);
    }
    
    /**
     * Send a verification code via email to confirm the transaction
     * @Rest\Post("/transaction/distribution/{id}/email", name="send_transaction_email_verification")
     * @Security("is_granted('ROLE_AUTHORISE_PAYMENT')")
     *
     * @SWG\Tag(name="Transaction")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param  Request $request
     * @param Assistance $assistance
     * @return Response
     */
    public function sendVerificationEmailAction(Request $request, Assistance $assistance)
    {
        $user = $this->getUser();
        try {
            $this->get('transaction.transaction_service')->sendVerifyEmail($user, $assistance);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return new Response("Email sent");
    }
    
    /**
     * Update the status of the transactions sent through external API
     * @Rest\Get("/transaction/distribution/{id}/pickup", name="update_transaction_status")
     * @Security("is_granted('ROLE_AUTHORISE_PAYMENT')")
     *
     * @SWG\Tag(name="Transaction")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param  Request $request
     * @param Assistance $assistance
     * @return Response
     */
    public function updateTransactionStatusAction(Request $request, Assistance $assistance)
    {
        $countryISO3 = $request->request->get('__country');
        $assistanceBeneficiaryMapper = $this->get(AssistanceBeneficiaryMapper::class);
        try {
            $beneficiaries = $this->get('transaction.transaction_service')->updateTransactionStatus($countryISO3, $assistance);
            return $this->json($assistanceBeneficiaryMapper->toMinimalTransactionArrays($beneficiaries));
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get the logs of the transaction
     * @Rest\Get("/distributions/{id}/logs", name="get_logs_transaction")
     * @Security("is_granted('ROLE_AUTHORISE_PAYMENT')")
     *
     * @SWG\Tag(name="Transaction")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Assistance $assistance
     * @return Response
     */
    public function getLogsTransactionAction(Assistance $assistance)
    {
        $user = $this->getUser();
        try {
            $this->get('transaction.transaction_service')->sendLogsEmail($user, $assistance);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return new Response("Email sent");
    }
    
    /**
     * Test transaction connection
     * @Rest\Get("/distributions/{id}/test", name="test_transaction")
     * @Security("is_granted('ROLE_AUTHORISE_PAYMENT')")
     *
     * @SWG\Tag(name="Transaction")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Request $request
     * @param Assistance $assistance
     * @return Response
     */
    public function getTestTransactionAction(Request $request, Assistance $assistance)
    {
        $countryISO3 = $request->request->get('__country');

        try {
            $response = $this->get('transaction.transaction_service')->testConnection($countryISO3, $assistance);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return new Response("Connection successful: " . json_encode($response));
    }

    /**
     * Check progression of transaction
     * @Rest\Get("/transaction/distribution/{id}/progression", name="progression_transaction")
     * @Security("is_granted('ROLE_AUTHORISE_PAYMENT')")
     *
     * @SWG\Tag(name="Transaction")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Assistance $assistance
     * @return Response
     */
    public function checkProgressionTransactionAction(Assistance $assistance)
    {
        $user = $this->getUser();

        try {
            $response = $this->get('transaction.transaction_service')->checkProgression($user, $assistance);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return new Response(json_encode($response));
    }

    /**
     * Get the credentials of financial provider's connection
     * @Rest\Get("/financial/provider", name="credentials_financial_provider")
     * @Security("is_granted('ROLE_AUTHORISE_PAYMENT')")
     *
     * @SWG\Tag(name="Transaction")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getFPCredentialAction(Request $request)
    {
        $country = $request->request->all()['__country'];

        try {
            $response = $this->get('transaction.transaction_service')->getFinancialCredential($country);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $json = $this->get('serializer')
            ->serialize($response, 'json');

        return new Response($json);
    }

    /**
     * Update the financial provider's credential
     * @Rest\Post("/financial/provider", name="update_financial_provider")
     * @Security("is_granted('ROLE_AUTHORISE_PAYMENT')")
     *
     * @SWG\Tag(name="Transaction")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function updateFPAction(Request $request)
    {
        $data = $request->request->all();

        try {
            $response = $this->get('transaction.transaction_service')->updateFinancialCredential($data);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $json = $this->get('serializer')
            ->serialize($response, 'json');

        return new Response($json);
    }

    /**
     * List of purchases by beneficiary.
     *
     * @Rest\Get("/transactions/purchases/beneficiary/{beneficiaryId}")
     * @ParamConverter("beneficiary", options={"mapping": {"beneficiaryId" : "id"}})
     * @Security("is_granted('ROLE_PROJECT_MANAGER')")
     *
     * @SWG\Tag(name="Transaction")
     * @SWG\Response(response=200, description="OK")
     *
     * @param Beneficiary $beneficiary
     * @return Response
     */
    public function purchasesAction(Beneficiary $beneficiary)
    {
        $result = $this->getDoctrine()->getRepository(PurchasedItem::class)->getPurchases($beneficiary);

        return $this->json($result);
    }

    /**
     * List of purchases by household.
     *
     * @Rest\Get("/transactions/purchases/household/{householdId}")
     * @ParamConverter("household", options={"mapping": {"householdId" : "id"}})
     * @Security("is_granted('ROLE_PROJECT_MANAGER')")
     *
     * @SWG\Tag(name="Transaction")
     * @SWG\Response(response=200, description="OK")
     *
     * @param Household $household
     * @return Response
     */
    public function purchasesOfHouseholdAction(Household $household)
    {
        $result = $this->getDoctrine()->getRepository(PurchasedItem::class)->getHouseholdPurchases($household);

        return $this->json($result);
    }
}
