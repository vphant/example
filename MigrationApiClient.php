<?php

namespace App\Service;

use App\Exception\MigrationApiException;
use Clue\React\Block;
use Clue\React\Buzz\Browser;
use Clue\React\Buzz\Message\ResponseException;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use RingCentral\Psr7\Response;
use Symfony\Bridge\Monolog\Logger;

/**
 * Client for migration-api
 */
class MigrationApiClient
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var array
     */
    protected $params;

    /**
     * @param LoggerInterface $logger
     * @param $params
     */
    public function __construct(LoggerInterface $logger, $params)
    {
        $this->logger = $logger;
        $this->params = $params;
    }

    /**
     * @param string $uri
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getData($uri, $limit, $offset)
    {
        $url = $this->params['url'] . $uri . '?limit=' . (int)$limit . '&offset=' . (int)$offset;

        return $this->asyncGetRequest($url);
    }

    /**
     * @param string $url
     * @return array
     */
    protected function asyncGetRequest($url)
    {
        $loop = Factory::create();
        $client = new Browser($loop);
        $authHeader = 'Basic ' . base64_encode($this->params['username'] . ':' . $this->params['password']);
        $promise = $client->get($url, ['Authorization' => $authHeader]);

        $response = null;
        try {
            $response = Block\await($promise, $loop);
        } catch (ResponseException $e) {
            $this->logger->error('[migration-api][error] ' . $e->getMessage());
        }

        /* @var Response $response */
        if (!is_null($response) && 200 === $response->getStatusCode()) {
            $result = json_decode($response->getBody()->getContents(), true);

            return $result;
        } else {
            $this->logger->error(sprintf('[migration-api][error] async request error. url: %s', $url));
        }

        throw new MigrationApiException('Migration api error');
    }

}
