<?php
namespace OKohei\OpenAssets\Tests\Transactions;

class AssetDefinitionLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $testnet = NetworkFactory::bitcoinTestnet();
        Bitcoin::setNetwork($testnet);
    }
}
