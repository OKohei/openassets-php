<?php

namespace OKohei\OpenAssets\Protocols;

interface  OutputType
{
    public function get($transactionHash, $outputIndex);

    public function put($transactionHash, $outputIndex, $output);
}
