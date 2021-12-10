<?php

namespace App\Http\JsonRpc\Sources;

use App\Http\JsonRpc\Result\BookIrbisQueryResult;

class BonchJsonRpc
{
    /** @var JsonRpcService $service */
    private $jsonRpcService;

    private function __construct(JsonRpcService $service)
    {
        $this->jsonRpcService = $service;
    }

    public static function getService()
    {
        return new self(JsonRpcService::create('http://lib.sut.ru//jirbis2_spbgut//components//com_irbis//ajax_provider.php?task=rpc&class=jwrapper', 1, 1)
        );
    }

    /**
     * @param $queryString
     * @param $limit
     * @param $offset
     * @return array|BookIrbisQueryResult
     */
    public function getBookResult($queryString, $limit, $offset)
    {
        //((<.>V=EXT<.>)*(<.>HD=J<.>+<.>HD=J0<.>+<.>HD=J2<.>+<.>HD=J4<.>+<.>HD=J5<.>))^(<.>TEK=http$<.>)
        $params = [
            0 => "IBIS",
            1 => '((<.>V=EXT<.>)*' . $queryString . ')^(<.>TEK=http$<.>)',
            2 => "",
            //offset
            3 => $offset,
            //limit
            4 => $limit,
            5 => ["brief" => ["format" => "@jbrief", "type" => "bo"]]
        ];

        //отправляем пачку запросов
        $requests = [
            $this->jsonRpcService->request(1, 'rpc_auth', [1, 1]),
            $this->jsonRpcService->request(2, 'req_full_count', $params),
            $this->jsonRpcService->request(3, 'find_jrecords', $params),
        ];

        $response = $this->jsonRpcService->sendRequest($requests);

        if (empty($response)) {
            return [];
        }

        return BookIrbisQueryResult::createResult($response[1]->getRpcResult(), $response[2]->getRpcResult());
    }

    public function downloadPdf($pathes)
    {

        $resultPdf = $this->jsonRpcService->sendAll([
            $this->jsonRpcService->request(1, 'rpc_auth', [1, 1]),
            $this->jsonRpcService->request(2, 'getfile', [10, 'IBIS', $path]),
        ]);

    }
}
