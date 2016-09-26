<?php
namespace OKohei\OpenAssets\Transactions;

class InsufficientAssetQuantityError
{
    public function __construct()
    {
        $testnet = NetworkFactory::bitcoinTestnet();
        Bitcoin::setNetwork($testnet);
    }
}
