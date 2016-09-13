<?php

namespace OKohei\OpenAssets\Protocols;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Exception\ClientException;
class HttpAssetDefinitionLoader
{
    protected $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function load()
    {
        $client = new Client();
        try {
            $res = $client->request('GET', $this->url);
            if ($res->getStatusCode() != 200) {
                return null;
            }
            $body = $res->getBody();
            $definition = AssetDefinition::parseJson($body);
        } catch (TransferException $e) {
            return null;
        }
        return $definition;
    }
}
