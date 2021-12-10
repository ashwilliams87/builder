<?php

namespace App\Http\JsonRpc\Sources;

use Graze\GuzzleHttp\JsonRpc\Client as GrazeClient;
use Graze\GuzzleHttp\JsonRpc\Exception\RequestException;

class JsonRpcService
{
    protected $jsonRpcService;
    protected $login;
    protected $password;

    /**
     * Client constructor.
     * @param $jsonRpcService
     * @param $login
     * @param $password
     */
    private function __construct($jsonRpcService, $login, $password)
    {
        $this->login = $login;
        $this->password = $password;
        /** @var GrazeClient jsonRpcService */
        $this->jsonRpcService = $jsonRpcService;
    }

    /**
     * @param $params
     * @return array
     */
    public function sendRequest($requests): array
    {
        return $this->jsonRpcService->sendAll($requests);
    }

    public function request($id, $method, array $params = null)
    {
        return $this->jsonRpcService->request($id, $method, $params);
    }

    /**
     * @param $jsonRpcApiUrl
     * @param $login
     * @param $password
     * @return JsonRpcService
     */
    public static function create($jsonRpcApiUrl, $login, $password)
    {
        return new self(GrazeClient::factory($jsonRpcApiUrl), $login, $password);
    }


}
