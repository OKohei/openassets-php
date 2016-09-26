<?php
namespace OKohei\OpenAssets\Transactions;

use OKohei\OpenAssets\Protocols\AssetDefinitionLoader;
use OKohei\OpenAssets\Protocols\HttpAssetDefinitionLoader;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Address\AddressFactory;

class DustOutputError 
{
    public function __construct()
    {
        $testnet = NetworkFactory::bitcoinTestnet();
        Bitcoin::setNetwork($testnet);
    }
}
