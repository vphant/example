<?php

namespace App\Service;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;

class MongoNativeClient
{
    /**
     * @var array
     */
    protected $params;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @param array $params
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * @param string $name
     * @return Collection
     */
    public function getCollection($name)
    {
        return $this->getDatabase()->$name;
    }

    /**
     * @return Database
     */
    protected function getDatabase()
    {
        $defaultDb = $this->params['default_database'];

        return $this->getClient()->$defaultDb;
    }

    /**
     * @return Client
     */
    protected function getClient(): Client
    {
        if (null === $this->client) {
            return new Client(
                $this->params['url'],
                [
                    'username' => $this->params['username'],
                    'password' => $this->params['password'],
                    'authSource' => $this->params['auth_db'],
                ]
            );
        }

        return $this->client;
    }

}
