<?php

namespace Utils\Provider;

use DateTime;
use Entity\OrganizationServices;
use Entity\AssistanceBeneficiary;
use Entity\Assistance;
use Entity\Transaction;
use Exception;

/**
 * Class KHMFinancialProvider
 *
 * @package Utils\Provider
 */
class KHMFinancialProvider extends DefaultFinancialProvider
{
    /**
     * @var string
     */
    protected $url = "https://ir.wingmoney.com:9443/RestEngine";

    protected $url_prod = "https://api.wingmoney.com:8443/RestServer";

    /**
     * @var string
     */
    private $token;

    /**
     * @var DateTime
     */
    private $lastTokenDate;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var bool
     */
    private $production;

    /**
     * Get token to connect to API
     *
     * @param Assistance $assistance
     * @return object token
     * @throws Exception
     */
    public function getToken(Assistance $assistance)
    {
        $organizationWINGCashTransfer = $this->em->getRepository(OrganizationServices::class)->findOneByService(
            "WING Cash Transfer"
        );

        if (!$organizationWINGCashTransfer->getEnabled()) {
            $this->logger->error("Missing enabled configuration for Wing money service in DB", [$assistance]);
            throw new Exception("This service is not enabled for the organization");
        }

        $this->password = $organizationWINGCashTransfer->getParameterValue('password');
        $this->username = $organizationWINGCashTransfer->getParameterValue('username');
        $this->production = $organizationWINGCashTransfer->getParameterValue(
            'production'
        ) ? $organizationWINGCashTransfer->getParameterValue('production') : false;

        if (!$this->password || !$this->username) {
            $this->logger->error("Missing credentials for Wing money service in DB", [$assistance]);
            throw new Exception("This service has no parameters specified");
        }

        // $this->username = $FP->getUsername();
        // $this->password = base64_decode($FP->getPassword());

        $route = "/oauth/token";
        $body = [
            "username" => $this->username,
            "password" => $this->password,
            "grant_type" => "password",
            "client_id" => "third_party",
            "client_secret" => "16681c9ff419d8ecc7cfe479eb02a7a",
            "scope" => "trust",
        ];

        $this->token = $this->sendRequest($assistance, "POST", $route, $body);
        $this->lastTokenDate = new DateTime();

        return $this->token;
    }

    /**
     * Send money to one beneficiary
     *
     * @param string $phoneNumber
     * @param AssistanceBeneficiary $assistanceBeneficiary
     * @param float $amount
     * @param string $currency
     * @return Transaction
     * @throws Exception
     */
    public function sendMoneyToOne(
        string $phoneNumber,
        AssistanceBeneficiary $assistanceBeneficiary,
        float $amount,
        string $currency
    ) {
        $assistance = $assistanceBeneficiary->getAssistance();
        $route = "/api/v1/sendmoney/nonwing/commit";
        $body = [
            "amount" => $amount,
            "currency" => $currency,
            "sender_msisdn" => "012249184",
            "receiver_msisdn" => $phoneNumber,
            "sms_to" => "PAYEE",
        ];

        $sent = $this->sendRequest($assistance, "POST", $route, $body);
        if (property_exists($sent, 'error_code')) {
            $transaction = $this->createTransaction(
                $assistanceBeneficiary,
                '',
                new DateTime(),
                $currency . ' ' . $amount,
                0,
                $sent->message ?: ''
            );

            return $transaction;
        }

        $response = $this->getStatus($assistance, $sent->transaction_id);

        $transaction = $this->createTransaction(
            $assistanceBeneficiary,
            $response->transaction_id,
            new DateTime(),
            $response->amount,
            1,
            property_exists($response, 'message') ? $response->message : $sent->passcode
        );

        return $transaction;
    }

    /**
     * Get status of transaction
     *
     * @param Assistance $assistance
     * @param string $transaction_id
     * @return object
     * @throws Exception
     */
    public function getStatus(Assistance $assistance, string $transaction_id)
    {
        $route = "/api/v1/sendmoney/nonwing/txn_inquiry";
        $body = [
            "transaction_id" => $transaction_id,
        ];

        return $this->sendRequest($assistance, "POST", $route, $body);
    }

    /**
     * Send request to WING API for Cambodia
     *
     * @param Assistance $assistance
     * @param string $type type of the request ("GET", "POST", etc.)
     * @param string $route url of the request
     * @param array $body body of the request (optional)
     * @return mixed  response
     * @throws Exception
     */
    public function sendRequest(Assistance $assistance, string $type, string $route, array $body = [])
    {
        $requestUnique = uniqid();
        $requestID = "Request#$requestUnique: ";

        $this->logger->error(
            $requestID . "started for Assistance#" . $assistance->getId() . " of type $type to route $route"
        );

        $curl = curl_init();

        if (false === $curl) {
            $this->logger->error($requestID . "curl_init failed");
        } else {
            $this->logger->error($requestID . "Curl initialized");
        }

        $headers = [];

        // Not authentication request
        if (!preg_match('/\/oauth\/token/', $route)) {
            if (
                !$this->lastTokenDate ||
                (new DateTime())->getTimestamp() - $this->lastTokenDate->getTimestamp() > $this->token->expires_in
            ) {
                $this->getToken($assistance);
            }
            array_push(
                $headers,
                "Authorization: Bearer " . $this->token->access_token,
                "Content-type: application/json"
            );
            $body = json_encode((object) $body);
        } else { // Authentication request
            $body = http_build_query($body); // Pass body as url-encoded string
        }

        $this->logger->error($requestID . "Body built");

        $dir_root = $this->rootDir;
        $curlLog = $dir_root . "/../var/logs/curl_$requestUnique.log";

        $this->logger->error($requestID . "curl log in " . $curlLog);

        curl_setopt_array($curl, [
            CURLOPT_PORT => ($this->production ? "8443" : "9443"),
            CURLOPT_URL => ($this->production ? $this->url_prod : $this->url) . $route,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $type,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FAILONERROR => true,
            CURLINFO_HEADER_OUT => true,

            // verbose to debug
            CURLOPT_VERBOSE => true,
            CURLOPT_STDERR => fopen($curlLog, 'w+'),
        ]);

        $info = curl_getinfo($curl);

        foreach ($info as $key => $value) {
            if (is_array($value)) {
                $this->logger->error($requestID . "curl_getinfo $key = " . implode(', ', $value));
            } else {
                $this->logger->error($requestID . "curl_getinfo $key = " . $value);
            }
        }

        $this->logger->error(
            $requestID . "Route: " . ($this->production ? $this->url_prod : $this->url) . $route . "[port" . ($this->production ? "8443" : "9443") . "]"
        );

        $err = null;
        try {
            $response = curl_exec($curl);
        } catch (Exception $exception) {
            $this->logger->error($requestID . "curl_exec throw exception: " . $exception->getMessage());
            throw $exception;
        }

        $this->logger->error($requestID . "curl_exec done");
        if (false === $response) {
            $this->logger->error($requestID . "error branch, response === null");
            try {
                $err = curl_error($curl);
            } catch (Exception $exception) {
                $this->logger->error($requestID . "curl_error throw exception: " . $exception->getMessage());
                throw $exception;
            }
            $this->logger->error($requestID . " fails: " . $err);
        } else {
            $this->logger->error($requestID . "response OK, response !== null");
        }

        try {
            $duration = curl_getinfo($curl, CURLINFO_TOTAL_TIME);
            $this->logger->error($requestID . "Request time $duration s");
        } catch (Exception $exception) {
            $this->logger->error($requestID . "curl_getinfo throw exception: " . $exception->getMessage());
            throw $exception;
        }

        try {
            curl_close($curl);
        } catch (Exception $exception) {
            $this->logger->error($requestID . "curl_close throw exception: " . $exception->getMessage());
            throw $exception;
        }

        $this->logger->error($requestID . "curl_close done");

        $bodyString = '';
        // Record request
        if (is_array($body)) {
            foreach ($body as $item) {
                if ($bodyString == '') {
                    $bodyString .= $item;
                } else {
                    $bodyString .= ', ' . $item;
                }
            }
        } else {
            $bodyString = $body;
        }

        $data = [
            $this->from,
            (new DateTime())->format('d-m-Y h:i:s'),
            $info['url'],
            $info['http_code'],
            $response,
            $err,
            $bodyString,
        ];
        $this->recordTransaction($assistance, $data);

        $this->logger->error($requestID . "record logged into var/logs/record_{$assistance->getId()}.csv");

        if ($err) {
            $this->logger->error($requestID . __METHOD__ . " ended with error, throw exception");
            throw new Exception($err);
        } else {
            $this->logger->error($requestID . __METHOD__ . "ended correctly");
            $result = json_decode($response);

            return $result;
        }
    }
}
