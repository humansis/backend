<?php

declare(strict_types=1);

namespace Command\Crowdin;

use JsonException;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

trait CrowdinRequestTrait
{
    /**
     * @throws TransportExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws JsonException
     */
    private function makeRequest(string $method, string $endpoint, array $options = []): array
    {
        $url = 'https://api.crowdin.com/api/v2'.$endpoint;

        $options['headers']['Authorization'] = 'Bearer '.$this->crowdinApiKey;
        if (!isset($options['headers']['Content-Type'])) {
            $options['headers']['Content-Type'] = 'application/json';
        }

        $response = $this->client->request($method, $url, $options);
        $statusCode = $response->getStatusCode();
        $content = $response->getContent(false);
        $headers = $response->getHeaders(false);

        if ($statusCode >= 400) {
            $this->output->writeln('<error>'.$method.' '.$url."\n".$statusCode.': '.$content.'</error>');
            throw new \RuntimeException('Request failed with status code '.$statusCode);
        }

        return array_merge(
            ['headers' => $headers],
            json_decode($content, true, 512, JSON_THROW_ON_ERROR)
        );
    }
}