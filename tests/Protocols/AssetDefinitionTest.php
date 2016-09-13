<?php
namespace OKohei\OpenAssets\Tests\Protocols;

use OKohei\OpenAssets\Protocols\AssetDefinition;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Address\AddressFactory;

class AssetDefinitionTest extends \PHPUnit_Framework_TestCase
{
    private $json = '{"asset_ids":["AGHhobo7pVQN5fZWqv3rhdc324ryT7qVTB","AWo3R89p5REmoSyMWB8AeUmud8456bRxZL","AJk2Gx5V67S2wNuwTK5hef3TpHunfbjcmX"],"version":"1.0","divisibility":1,"name_short":"HAWSCoin","name":"MHAWS Coin","contract_url":"http://techmedia-think.hatenablog.com/","issuer":"Shigeyuki Azuchi","description":"The OpenAsset test description.","description_mime":"text/x-markdown; charset=UTF-8","type":"Currency","link_to_website":false}';

    public function __construct()
    {
        $testnet = NetworkFactory::bitcoinTestnet();
        Bitcoin::setNetwork($testnet);
    }

    public function testParseJson()
    {
        $assetDefinition = AssetDefinition::parseJson($this->json);
        $this->assertEquals(3, count($assetDefinition->assetIds));
        $this->assertEquals('AGHhobo7pVQN5fZWqv3rhdc324ryT7qVTB', $assetDefinition->assetIds[0]);
        $this->assertEquals('HAWSCoin', $assetDefinition->nameShort);
        $this->assertEquals('MHAWS Coin', $assetDefinition->name);
        $this->assertEquals('http://techmedia-think.hatenablog.com/', $assetDefinition->contractUrl);
        $this->assertEquals('Shigeyuki Azuchi', $assetDefinition->issuer);
        $this->assertEquals('The OpenAsset test description.', $assetDefinition->description);
        $this->assertEquals('text/x-markdown; charset=UTF-8', $assetDefinition->descriptionMime);
        $this->assertEquals('Currency', $assetDefinition->type);
        $this->assertEquals(1, $assetDefinition->divisibility);
        $this->assertFalse($assetDefinition->linkToWebsite);
        $this->assertNull($assetDefinition->iconUrl);
        $this->assertNull($assetDefinition->imageUrl);
        $this->assertEquals('1.0', $assetDefinition->version);
    }
    
    public function testToJson()
    {
        $assetDefinition = new AssetDefinition();
        $assetDefinition->assetIds[0] = 'AGHhobo7pVQN5fZWqv3rhdc324ryT7qVTB';
        $assetDefinition->assetIds[1] = 'AWo3R89p5REmoSyMWB8AeUmud8456bRxZL';
        $assetDefinition->assetIds[2] = 'AJk2Gx5V67S2wNuwTK5hef3TpHunfbjcmX';
        $assetDefinition->nameShort = 'HAWSCoin';
        $assetDefinition->name = 'MHAWS Coin';
        $assetDefinition->contractUrl = 'http://techmedia-think.hatenablog.com/';
        $assetDefinition->issuer = 'Shigeyuki Azuchi';
        $assetDefinition->description = 'The OpenAsset test description.';
        $assetDefinition->descriptionMime = 'text/x-markdown; charset=UTF-8';
        $assetDefinition->type = 'Currency';
        $assetDefinition->divisibility = 1;
        $assetDefinition->linkToWebsite = false;
        $assetDefinition->version = '1.0';
        $assetDefinition->proofOfAuthenticity = false;
        //ToDo:: think about what's the best way of json format (with _ or with Capital?)
        //$this->assertEquals($this->json, $assetDefinition->toJson());
    }

    public function testProofOfAuthenticity()
    {
        //ToDo:: this could be only ruby thingy...
    }
}
