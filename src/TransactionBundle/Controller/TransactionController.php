<?php

namespace TransactionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JMS\Serializer\SerializationContext;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use DistributionBundle\Entity\DistributionData;

/**
 * Class TransactionController
 * @package TransactionBundle\Controller
 */
class TransactionController extends Controller
{

    /**
     * Send money to distribution beneficiaries via country financial provider
     * @Rest\Post("/transaction/distribution/{id}/send", name="send_money_for_distribution")
     * @Security("is_granted('ROLE_AUTHORISE_PAYMENT')")
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
     * @param DistributionData $distributionData
     * @return Response
     */
    public function postTransactionAction(Request $request, DistributionData $distributionData)  {
        $countryISO3 = $request->request->get('__country');
        $code = $request->request->get('code');
        $user = $this->getUser();

        $code = trim(preg_replace('/\s+/', ' ', $code));

        $validatedTransaction = $this->get('transaction.transaction_service')->verifyCode($code, $user, $distributionData);
        if (! $validatedTransaction) {
            return new Response("The supplied code did not match. The transaction cannot be executed", Response::HTTP_BAD_REQUEST);
        }
        
        try {
            $response = $this->get('transaction.transaction_service')->sendMoney($countryISO3, $distributionData, $user);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        
        $json = $this->get('jms_serializer')
            ->serialize($response, 'json', SerializationContext::create()->setSerializeNull(true)->setGroups(["ValidatedDistribution"]));
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
     * @param DistributionData $distributionData 
     * @return Response
     */
    public function sendVerificationEmailAction(Request $request, DistributionData $distributionData) {
        $user = $this->getUser();
        try {
            $this->get('transaction.transaction_service')->sendVerifyEmail($user, $distributionData);
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
     * @param DistributionData $distributionData 
     * @return Response
     */
    public function updateTransactionStatusAction(Request $request, DistributionData $distributionData) {
        $countryISO3 = $request->request->get('__country');
        try {
            $beneficiaries = $this->get('transaction.transaction_service')->updateTransactionStatus($countryISO3, $distributionData);
            $json = $this->get('jms_serializer')
            ->serialize($beneficiaries, 'json');
            return new Response($json);
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
     * @param DistributionData $distributionData
     * @return Response
     */
    public function getLogsTransactionAction(DistributionData $distributionData) {
        $user = $this->getUser();
        try {
            $this->get('transaction.transaction_service')->sendLogsEmail($user, $distributionData);
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
     * @param DistributionData $distributionData 
     * @return Response
     */
    public function getTestTransactionAction(Request $request, DistributionData $distributionData) {
        $countryISO3 = $request->request->get('__country');

        try {
            $response = $this->get('transaction.transaction_service')->testConnection($countryISO3, $distributionData);
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
     * @param DistributionData $distributionData
     * @return Response
     */
    public function checkProgressionTransactionAction(DistributionData $distributionData) {
        $user = $this->getUser();

        try {
            $response = $this->get('transaction.transaction_service')->checkProgression($user, $distributionData);
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
    public function getFPCredentialAction(Request $request) {
        $country = $request->request->all()['__country'];

        try {
            $response = $this->get('transaction.transaction_service')->getFinancialCredential($country);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $json = $this->get('jms_serializer')
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
    public function updateFPAction(Request $request) {
        $data = $request->request->all();

        try {
            $response = $this->get('transaction.transaction_service')->updateFinancialCredential($data);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $json = $this->get('jms_serializer')
            ->serialize($response, 'json');

        return new Response($json);
    }

}
