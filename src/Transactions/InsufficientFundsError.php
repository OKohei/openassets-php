<?php
namespace OKohei\OpenAssets\Tests\Protocols;

use OKohei\OpenAssets\Protocols\AssetDefinitionLoader;
use OKohei\OpenAssets\Protocols\HttpAssetDefinitionLoader;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Address\AddressFactory;

class InsufficientFundsError
{
    public function __construct()
    {
        $testnet = NetworkFactory::bitcoinTestnet();
        Bitcoin::setNetwork($testnet);
    }
}
