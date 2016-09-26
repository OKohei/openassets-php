<?php
namespace OKohei\OpenAssets\Transactions;

class TransactionBuildError
{
    public function __construct()
    {
        $testnet = NetworkFactory::bitcoinTestnet();
        Bitcoin::setNetwork($testnet);
    }
}
