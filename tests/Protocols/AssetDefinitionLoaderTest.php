<?php
namespace OKohei\OpenAssets\Tests\Protocols;

use OKohei\OpenAssets\Protocols\AssetDefinitionLoader;
use OKohei\OpenAssets\Protocols\HttpAssetDefinitionLoader;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Address\AddressFactory;

class AssetDefinitionLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $testnet = NetworkFactory::bitcoinTestnet();
        Bitcoin::setNetwork($testnet);
    }

    public function testHttpLoad()
    {
        $assetDefinitionLoader = new AssetDefinitionLoader('http://goo.gl/fS4mEj');
        $this->assertInstanceOf(HttpAssetDefinitionLoader::class, $assetDefinitionLoader->loader);
    }

    public function testInvalidScheme()
    {
        $assetDefinitionLoader = new AssetDefinitionLoader('<http://www.caiselian.com>');
        $this->assertNull($assetDefinitionLoader->loadDefinition());
    }
}
