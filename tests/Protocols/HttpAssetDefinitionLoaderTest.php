<?php
namespace OKohei\OpenAssets\Tests\Protocols;

use OKohei\OpenAssets\Protocols\HttpAssetDefinitionLoader;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Address\AddressFactory;

class HttpAssetDefinitionLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $testnet = NetworkFactory::bitcoinTestnet();
        Bitcoin::setNetwork($testnet);
    }

    public function testLoadOverHttp()
    {
        $loader = new HttpAssetDefinitionLoader('http://goo.gl/fS4mEj');
        $assetDefinition = $loader->load();
        $this->assertEquals(3, count($assetDefinition->assetIds));
        $this->assertEquals('AGHhobo7pVQN5fZWqv3rhdc324ryT7qVTB', $assetDefinition->assetIds[0]);
        $this->assertEquals('HAWSCoin', $assetDefinition->nameShort);
        $this->assertEquals('MHAWS Coin', $assetDefinition->name);
        $this->assertEquals('http://techmedia-think.hatenablog.com/', $assetDefinition->contractUrl);
        $this->assertEquals('Shigeyuki Azuchi', $assetDefinition->issuer);
        $this->assertEquals('カラーコインの実験用通貨です。', $assetDefinition->description);
        $this->assertEquals('text/x-markdown; charset=UTF-8', $assetDefinition->descriptionMime);
        $this->assertEquals('Currency', $assetDefinition->type);
        $this->assertEquals(1, $assetDefinition->divisibility);
        $this->assertFalse($assetDefinition->linkToWebsite);
        $this->assertNull($assetDefinition->iconUrl);
        $this->assertNull($assetDefinition->imageUrl);
        $this->assertEquals('1.0', $assetDefinition->version);
    }

    public function testFailLoadOverHttp()
    {
        $loader = new HttpAssetDefinitionLoader('https://github.com/haw-itn/openassets-ruby/hoge');
        $assetDefinition = $loader->load();
        $this->assertNull($assetDefinition);
    }
}
