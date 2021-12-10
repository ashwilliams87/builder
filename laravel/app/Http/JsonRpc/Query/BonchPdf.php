<?php

namespace App\Http\JsonRpc\Sources\Bonch;


use App\Http\Helper\JsonRpc\Irbis\JsonRpcService;
use Graze\GuzzleHttp\JsonRpc\Client;
use \Exception as Error;

class BonchPdf
{
    private $pathes = [];

    public static function createBuilder()
    {
        return new self();
    }


    public function inPath(string $pathes)
    {
        foreach ($pathes as $path) {
            $this->pathes[]= $path;
        }
        $this->pathes = $path;
        return $this;
    }

    public function path()
    {

    }

    public function getQueryResult($directory): PdfQueryResult
    {
        if (empty($directory) || !is_dir($directory)) {
            throw new Error('Ошибка с директриями');
        }


        $client = Client::factory('http://lib.sut.ru//jirbis2_spbgut//components//com_irbis//ajax_provider.php?task=rpc&class=jwrapper');


        $pdfPathes = $this->getPathes();

        foreach ($pdfPathes as $pathKey => $path) {
            //TODO
            $resultPdf = $client->sendAll(

                [
                    $client->request(1, 'rpc_auth', [1, 1]),
                    $client->request(2, 'getfile', [10, 'IBIS', $path]),
                ]
            );

            $file_content_encoded = $resultPdf[1]->getRpcResult();
            file_put_contents($directory . $pathKey, base64_decode($file_content_encoded));
        }




        //Сделать через статическое поле?
        return $request->getQueryResult($params);


    }

}
