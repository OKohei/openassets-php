<?php
namespace OKohei\OpenAssets\Tests\Transactions;

use OKohei\OpenAssets\Util;
use OKohei\OpenAssets\Googl;
use OKohei\OpenAssets\Transactions\SpendableOutput;
use OKohei\OpenAssets\Transactions\TransactionBuilder;
use OKohei\OpenAssets\Transactions\TransferParameters;
use OKohei\OpenAssets\Transactions\OutPoint;
use OKohei\OpenAssets\Protocols\TransactionOutput;
use OKohei\OpenAssets\Protocols\MarkerOutput;
use OKohei\OpenAssets\Protocols\OutputType;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Amount;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Transaction\OutPoint as WaspOutPoint;
use BitWasp\Bitcoin\Transaction\TransactionOutput as WaspTransactionOutput;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Crypto\Random\Random;

use Exception;


use Aws\S3\S3Client;
use Guzzle\Http\EntityBody;


class TransactionBuilderTest extends \PHPUnit_Framework_TestCase
{

    public function testIssueAsset()
    {
        $testnet = NetworkFactory::bitcoin();
        Bitcoin::setNetwork($testnet);
        $unspentOutputs = $this->genOutputs([
            [20, 'source', 'ALn3aK1fSuG27N96UGYB1kUYUpGKRhBuBC', 50, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8220'],
            [15, 'source', null, 0, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8221'],
            [10, 'source', null, 0, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8222']
        ]);
        $target = new TransactionBuilder(10);
        $spec = new TransferParameters($unspentOutputs, 'akD71LJfDrVkPUg7dSZq6acdeDqgmHjrc2Q', 'akD71LJfDrVkPUg7dSZq6acdeDqgmHjrc2Q', 1000);

        $result = $target->issueAsset($spec, 'metadata', 5);
        $this->assertEquals('010000000221821bb2d29ac1063e040fea3d3f4f95b9d297642615853cf9997111df2a7e8a0100000006736f75726365ffffffff22821bb2d29ac1063e040fea3d3f4f95b9d297642615853cf9997111df2a7e8a0200000006736f75726365ffffffff030a000000000000001976a91417797f19075a56e7d4fc23f2ea5c17020fd3b93d88ac0000000000000000126a104f41010001e807086d657461646174610a000000000000001976a91417797f19075a56e7d4fc23f2ea5c17020fd3b93d88ac00000000', $result->getBuffer()->getHex());
        $this->assertCount(2, $result->getInputs());
        $this->assertCount(3, $result->getOutputs());

        $input0 = $result->getInputs()[0];
        $this->assertEquals('8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8221', $input0->getOutPoint()->getTxId()->getHex());
        $this->assertEquals(1, $input0->getOutPoint()->getVout());
        $this->assertEquals('source', $input0->getScript()->getBuffer()->getBinary());

        $input1 = $result->getInputs()[1];
        $this->assertEquals('8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8222', $input1->getOutPoint()->getTxId()->getHex());
        $this->assertEquals(2, $input1->getOutPoint()->getVout());
        $this->assertEquals('source', $input0->getScript()->getBuffer()->getBinary());

        $output0 = $result->getOutputs()[0];
        $this->assertEquals(10, $output0->getValue());
        $this->assertEquals('OP_DUP OP_HASH160 17797f19075a56e7d4fc23f2ea5c17020fd3b93d OP_EQUALVERIFY OP_CHECKSIG', $output0->getScript()->getScriptParser()->getHumanReadable());

        $output1 = $result->getOutputs()[1];
        $payload = MarkerOutput::parseScript($output1->getScript()->getBuffer());
        $markerOutput = MarkerOutput::deserializePayload($payload);
        $this->assertEquals(0, $output1->getValue());
        $this->assertEquals([1000], $markerOutput->getAssetQuantities());
        $this->assertEquals('metadata', $markerOutput->getMetadata());

        $output2 = $result->getOutputs()[2];
        $this->assertEquals(10, $output2->getValue());
        $this->assertEquals('OP_DUP OP_HASH160 17797f19075a56e7d4fc23f2ea5c17020fd3b93d OP_EQUALVERIFY OP_CHECKSIG', $output2->getScript()->getScriptParser()->getHumanReadable());
    }


    public function testCollectUncoloredOutputs()
    {
        $unspentOutputs = $this->genOutputs([
            [20, 'source', 'ALn3aK1fSuG27N96UGYB1kUYUpGKRhBuBC', 50, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8220'],
            [15, 'source', null, 0, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8221'],
            [10, 'source', null, 0, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8222']
            ]);
        $uncoloredOutputs = TransactionBuilder::collectUncoloredOutputs($unspentOutputs, 2 * 10 + 5);
        $inputs = $uncoloredOutputs[0];
        $totalAmount = $uncoloredOutputs[1];
        $this->assertCount(2, $inputs);
        foreach($inputs as $output) {
            $this->assertNull($output->output->assetId);
        }
        $this->assertEquals(25, $totalAmount);
    }

    public function testCollectUncoloredOutputsInsufficient()
    {
        $unspentOutputs = $this->genOutputs([
            [20, 'source', 'ALn3aK1fSuG27N96UGYB1kUYUpGKRhBuBC', 50, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8220'],
            [15, 'source', 'ALn3aK1fSuG27N96UGYB1kUYUpGKRhBuBC', 0, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8221'],
            [10, 'source', null, 0, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8222']
            ]);
        try {
            $uncoloredOutputs = TransactionBuilder::collectUncoloredOutputs($unspentOutputs, 2 * 10 + 5);
        } catch (Exception $e) {
            $this->assertTrue(true);
        } 
    }

    //p2pkh section
    public function testGetUncoloredOutputP2pkh()
    {
        $target = new TransactionBuilder(10);
        try {
            $target->getUncoloredOutput('1F2AQr6oqNtcJQ6p9SiCLQTrHuM9en44H8', 9);
        } catch (Exception $e) {
            $this->assertTrue(true);
        } 
        $txOut = $target->getUncoloredOutput('1F2AQr6oqNtcJQ6p9SiCLQTrHuM9en44H8', 11);
        $this->assertInstanceOf(WaspTransactionOutput::class, $txOut);
        $this->assertEquals('OP_DUP OP_HASH160 99ca0870645ebc81abbe0806318efc9ff474e540 OP_EQUALVERIFY OP_CHECKSIG', $txOut->getScript()->getScriptParser()->getHumanReadable());
    }
    
    public function testGetColoredOutputP2pkh()
    {
        $target = new TransactionBuilder(10);
        $txOut = $target->getColoredOutput('1F2AQr6oqNtcJQ6p9SiCLQTrHuM9en44H8');
        $this->assertInstanceOf(WaspTransactionOutput::class, $txOut);
        $this->assertEquals('OP_DUP OP_HASH160 99ca0870645ebc81abbe0806318efc9ff474e540 OP_EQUALVERIFY OP_CHECKSIG', $txOut->getScript()->getScriptParser()->getHumanReadable());
    }
    
    public function testCollectColoredOutputP2pkh()
    {
        $unspentOutputs = $this->genOutputs([
            [20, 'source', 'AVQ1hnBhEyaNPk6sS2kpmav2YkyXqrwoUT', 50, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8220'],
            [15, 'source', 'ALn3aK1fSuG27N96UGYB1kUYUpGKRhBuBC', 0, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8221'],
            [10, 'source', 'AVQ1hnBhEyaNPk6sS2kpmav2YkyXqrwoUT', 27, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8222']
        ]);

        $results = TransactionBuilder::collectColoredOutputs($unspentOutputs, 'AVQ1hnBhEyaNPk6sS2kpmav2YkyXqrwoUT', 60);
        $outputs = $results[0];
        $amount = $results[1];
        $this->assertCount(2, $outputs);
        foreach($outputs as $output) {
            $this->assertEquals('AVQ1hnBhEyaNPk6sS2kpmav2YkyXqrwoUT', $output->output->assetId);
        }
        $this->assertEquals(77, $amount);
    }

    //p2sh address
    public function testGetUncoloredOutputP2sh()
    {
        $testnet = NetworkFactory::bitcoinTestnet();
        Bitcoin::setNetwork($testnet);
        $target = new TransactionBuilder(10);
        try {
            $target->getUncoloredOutput('2MtHrGGHzuiW18MF113xJmZXZ8AuBmbeXo4', 9);
        } catch (Exception $e) {
            $this->assertTrue(true);
        } 
        $txOut = $target->getUncoloredOutput('2MtHrGGHzuiW18MF113xJmZXZ8AuBmbeXo4', 11);
        $this->assertInstanceOf(WaspTransactionOutput::class, $txOut);
        $this->assertEquals('OP_HASH160 0b773d2e93630161ea0c9cb6aa80c758d131cf9e OP_EQUAL', $txOut->getScript()->getScriptParser()->getHumanReadable());
    }
    
    public function testGetColoredOutputP2sh()
    {
        $target = new TransactionBuilder(10);
        $txOut = $target->getColoredOutput('2MtHrGGHzuiW18MF113xJmZXZ8AuBmbeXo4');
        $this->assertInstanceOf(WaspTransactionOutput::class, $txOut);
        $this->assertEquals('OP_HASH160 0b773d2e93630161ea0c9cb6aa80c758d131cf9e OP_EQUAL', $txOut->getScript()->getScriptParser()->getHumanReadable());
    }
    
    public function testCollectColoredOutputP2shInsufficient()
    {
        $unspentOutputs = $this->genOutputs([
            [20, 'source', 'AVQ1hnBhEyaNPk6sS2kpmav2YkyXqrwoUT', 50, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8220'],
            [15, 'source', 'ALn3aK1fSuG27N96UGYB1kUYUpGKRhBuBC', 10, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8221'],
            [10, 'source', 'AVQ1hnBhEyaNPk6sS2kpmav2YkyXqrwoUT', 27, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8222']
        ]);

        try {
            $results = TransactionBuilder::collectColoredOutputs($unspentOutputs, 'ALn3aK1fSuG27N96UGYB1kUYUpGKRhBuBC', 60);
        } catch (Exception $e) {
            $this->assertTrue(true);
        } 
    }
    
    public function testOtsuriNeedsCollectBtc()
    {
        $testnet = NetworkFactory::bitcoin();
        Bitcoin::setNetwork($testnet);
        $from = 'akXDPMMHHBrUrd1fM756M1GSB8viVAwMyBk';
        $to = 'AVQ1hnBhEyaNPk6sS2kpmav2YkyXqrwoUT';
        $unspentOutputs = $this->genOutputs([
            [600, 'source', 'AVQ1hnBhEyaNPk6sS2kpmav2YkyXqrwoUT', 50, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8220'],
            [600, 'source', 'ALn3aK1fSuG27N96UGYB1kUYUpGKRhBuBC', 10, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8221'],
            [600, 'source', 'AVQ1hnBhEyaNPk6sS2kpmav2YkyXqrwoUT', 50, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8222'],
            [10700, 'source', null, 0, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8223'],
            [800, 'source', null, 0, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8224'],
            [999988, 'source', null, 0, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8225']
            ]);
        $builder = new TransactionBuilder(600);
        $spec = new TransferParameters($unspentOutputs, 'akD71LJfDrVkPUg7dSZq6acdeDqgmHjrc2Q', $from, 66, 2);
        # 作成されるアウトプット
        # アセット分割送付のアウトプット２つ
        # アセットのおつりのアウトプット１つ
        # BTCのおつりのアウトプット１つ（おつり額＝ 600*2 - 600*3 - 手数料(10000) = -10600）
        # おつり額がマイナスなので、uncoloredなUTXを収集＝10700
        # おつり再計算＝100
        # おつり額がdust_limitより低いので再度UTXO収集
        # marker output
        $tx = $builder->transferAsset($to, $spec, $from, 10000);
        $this->assertCount(4, $tx->getInputs());
        $this->assertCount(5, $tx->getOutputs());
        $payload = MarkerOutput::parseScript($tx->getOutputs()[0]->getScript()->getBuffer());
        $markerOutput = MarkerOutput::deserializePayload($payload);
        $this->assertEquals([33, 33, 34], $markerOutput->getAssetQuantities());
        $this->assertEquals(600,$tx->getOutputs()[1]->getValue());
        $this->assertEquals(600,$tx->getOutputs()[2]->getValue());
        $this->assertEquals(600,$tx->getOutputs()[3]->getValue());
        $this->assertEquals(900,$tx->getOutputs()[4]->getValue());
        $this->assertEquals('010000000420821bb2d29ac1063e040fea3d3f4f95b9d297642615853cf9997111df2a7e8a0000000006736f75726365ffffffff22821bb2d29ac1063e040fea3d3f4f95b9d297642615853cf9997111df2a7e8a0200000006736f75726365ffffffff23821bb2d29ac1063e040fea3d3f4f95b9d297642615853cf9997111df2a7e8a0300000006736f75726365ffffffff24821bb2d29ac1063e040fea3d3f4f95b9d297642615853cf9997111df2a7e8a0400000006736f75726365ffffffff0500000000000000000b6a094f410100032121220058020000000000001976a91417797f19075a56e7d4fc23f2ea5c17020fd3b93d88ac58020000000000001976a91417797f19075a56e7d4fc23f2ea5c17020fd3b93d88ac58020000000000001976a914de20a2d5a57ee40ce9a4ce14cf06a6c2c6ffe29788ac84030000000000001976a914de20a2d5a57ee40ce9a4ce14cf06a6c2c6ffe29788ac00000000', $tx->getHex());
    }
    
    public function testMultipleAddressInputsTransfer()
    {
        //ToDo::
        $from0 = '1DLSeqWwHJj3XrmsXQ5QzZZ4LDgp4gRvF7';
        $from1 = '1HhgpWTatNTb4UK4mm23QVpZKLm1GQTfkX';
        $to = '1HXEqrZxXy5nw7Us8ozFZ1Vwx37dGFL5wC';
        $change = '1F2AQr6oqNtcJQ6p9SiCLQTrHuM9en44H8';
        $assetId = 'AVQ1hnBhEyaNPk6sS2kpmav2YkyXqrwoUT';
        $unspentOutputs1 = $this->genOutputs([[1000, 'source', $assetId, 500, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8220']]);
        $unspentOutputs2 = $this->genOutputs([[1000, 'source', $assetId, 500, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8221']]);
        $unspentOutputs3 = $this->genOutputs([[3000, 'source', null, 0, '8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8222']]);

        $builder = new TransactionBuilder(1000);
        $assetTransferSpecs = [
            [$assetId, new TransferParameters($unspentOutputs1, Util::toOaAddress($to), Util::toOaAddress($from0), 100)],
            [$assetId, new TransferParameters($unspentOutputs2, Util::toOaAddress($to), Util::toOaAddress($from1), 100)]
            ];

        $btcTransferSpecs = [new TransferParameters($unspentOutputs3, null, $change, 0)];
        $tx = $builder->transfer($assetTransferSpecs, $btcTransferSpecs, 0);
        $payload = MarkerOutput::parseScript($tx->getOutputs()[0]->getScript()->getBuffer());
        $markerOutput = MarkerOutput::deserializePayload($payload);
        $this->assertEquals([100,400,100,400], $markerOutput->getAssetQuantities());
        $this->assertEquals($to, Util::scriptToAddress($tx->getOutputs()[1]->getScript()));
        $this->assertEquals($from0,Util::scriptToAddress($tx->getOutputs()[2]->getScript()));
        $this->assertEquals($to,Util::scriptToAddress($tx->getOutputs()[3]->getScript()));
        $this->assertEquals($from1,Util::scriptToAddress($tx->getOutputs()[4]->getScript()));
        $this->assertEquals(1000,$tx->getOutputs()[5]->getValue());
        $this->assertEquals('010000000320821bb2d29ac1063e040fea3d3f4f95b9d297642615853cf9997111df2a7e8a0000000006736f75726365ffffffff21821bb2d29ac1063e040fea3d3f4f95b9d297642615853cf9997111df2a7e8a0000000006736f75726365ffffffff22821bb2d29ac1063e040fea3d3f4f95b9d297642615853cf9997111df2a7e8a0000000006736f75726365ffffffff0600000000000000000e6a0c4f4101000464900364900300e8030000000000001976a914b53a0bdfaedaa1bfbb5bf8dd60f03012c737de5988ace8030000000000001976a914874ed380e2d48317f0842e6dbd09a493b808438f88ace8030000000000001976a914b53a0bdfaedaa1bfbb5bf8dd60f03012c737de5988ace8030000000000001976a914b733e3ec4a3bebaf08bbd8005a085a5fbf4fe65c88ace8030000000000001976a91499ca0870645ebc81abbe0806318efc9ff474e54088ac00000000', $tx->getHex());
    }

    private function genOutputs($definition)
    {
        $results = [];
        for ($i = 0; $i < count($definition); $i++) {
            $results[] = new SpendableOutput(
                new OutPoint($definition[$i][4], isset($definition[$i][5]) ? $definition[$i][5] : $i),
                new TransactionOutput(
                    $definition[$i][0],//value
                    ScriptFactory::fromHex(bin2hex($definition[$i][1])),//script
                    $definition[$i][2],//asset id
                    $definition[$i][3] //assetquantity
                )
            );
        }
        return $results;
    }

    public function testGenerateTransactionSigned()
    {
        $amount = new Amount();
        $ec = Bitcoin::getEcAdapter();
        $testnet = NetworkFactory::bitcoinTestnet();
        Bitcoin::setNetwork($testnet);
        $this->generateNewAddress();
        $factory = new PrivateKeyFactory();
        $key = $factory->fromWif('93G5SGnDkv7KxJv57UomznoFtjDwYrhy7a7QqSQq2S8uY36GWy4');
        $btcAddress = (new PayToPubKeyHashAddress($key->getPubKeyHash()))->getAddress();
        $scriptPubKey = ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPubKeyHash());
        $oaAddress = Util::toOaAddress($btcAddress);
        $satoshi = $amount->toSatoshis(0.001);
        $outPoint = new OutPoint('2177e661be02e202b0b707d222f5009fae65019ececcc01ef82a1e71841e076a', 1);
        $txOut = new TransactionOutput($satoshi, ScriptFactory::fromHex(''), null, 0);
        $spendableOutput = new SpendableOutput($outPoint, $txOut);
        
        $builder = new TransactionBuilder($amount->toSatoshis(0.0003));
        $spec = new TransferParameters([$spendableOutput], $oaAddress, $oaAddress, 10);
        $metadata = 'https://goo.gl/eYWLnY';//$this->generateAssetUrl(Util::generateAssetId($key->getPublicKey()));
        $tx = $builder->issueAsset($spec, $metadata, 20000);

        $signed = new Signer($tx, $ec);
        $signed->sign(0, $key, new WaspTransactionOutput($satoshi, $scriptPubKey));
        echo $signed->get()->getHex().PHP_EOL;
        $amount = new Amount();
        echo $amount->toBtc($signed->get()->getBuffer()->getSize() * 160);
        //echo $signed->get()->get().PHP_EOL;
    }

    private function generateNewAddress()
    {
        $factory = new PrivateKeyFactory();
        $key = $factory->generateUncompressed(new Random());
        //echo $key->toWif().PHP_EOL;
        //echo $key->getAddress()->getAddress().PHP_EOL;
        //echo UtiL::toOaAddress($key->getAddress()->getAddress()).PHP_EOL;
        //echo UtiL::generateAssetId($key->getPublicKey()).PHP_EOL;
    }
//
//    private function generateAssetUrl($assetId)
//    {
//        $s3 = S3Client::factory([
//            'version' => 'latest',
//            'region'  => 'ap-northeast-1',
//            'credentials' => [
//            ]
//        ]);
//
//        $data = [
//            "asset_ids"=> [
//                $assetId
//            ],
//            "contract_url" => "https://goo.gl/pCycYG",
//            "name_short" => "hirokings",
//            "name" => "中村ひろき",
//            "issuer" =>  "ヒロキング",
//            "description" =>  "ヒロキングが発行するテストコインです",
//            "description_mime" =>  "text/x-markdown; charset=UTF-8",
//            "type" =>  "AccessToken",
//            "divisibility" =>  2,
//            "link_to_website"=> true,
//            "icon_url" =>  "http://nakamurahiroki.com/wp/wp-content/uploads/2015/10/hiroki.jpg",
//            "image_url" =>  "http://nakamurahiroki.com/wp/wp-content/uploads/2015/10/hiroki.jpg",
//            "version" =>  "1.0"
//            ];
//        $key =  $assetId;
//        $bucket = 'pi-img';
//        
//        $res = $s3->putObject([
//            'Bucket' => $bucket,
//            'Key' => $key,
//            'Body' => json_encode($data, JSON_PRETTY_PRINT),
//            'ContentType'   => 'application/json; charset=utf-8',
//            'ACL' => 'public-read'
//        ]);
//
//        $url = $s3->getobjecturl($bucket, $key);
//        $googl = new Googl('AIzaSyDvctiJCKwsBrNgsleAC6YMwh8Zu3FXB_s');
//        return 'u='.$googl->shorten($url);
//    }
//
//    public function testTransfer()
//    {
//        $this->generateAssetUrl(Util::toOaAddress('mmwGgy3TXiJngVcav4g2bJPnJSErnPoL75'));
//        $amount = new Amount();
//        $ec = Bitcoin::getEcAdapter();
//        $testnet = NetworkFactory::bitcoinTestnet();
//        Bitcoin::setNetwork($testnet);
//        $key = PrivateKeyFactory::fromWif('92pZwBKJmnswJcvUozFcK9oSuyfSycsZCDW26DRKP9GuRPUpQBT');
//        $scriptPubKey = ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPublicKey());
//        $btcAddress = $key->getAddress()->getAddress();
//        $assetId = 'odmpJMHKpuR4bv19SSHefgWFJAToii226d';
//        $from = Util::toOaAddress($btcAddress);
//        $to = Util::toOaAddress('mmwGgy3TXiJngVcav4g2bJPnJSErnPoL75');
//        $satoshi = $amount->toSatoshis(0.03);
//        $unspentOutputs = $this->genOutputs([
//            [$satoshi, 'source', $assetId, 25, '635aa40960c0fd82734b150071067b0133d30ebc9b46560a8005395f2209532d', 1]
//            ]);
//        $builder = new TransactionBuilder($amount->toSatoshis(0.007));
//        $spec = new TransferParameters($unspentOutputs, $to, $from, 5);
//        $tx = $builder->transferAsset($assetId, $spec, $from, $amount->toSatoshis(0.0005));
//        $signed = new Signer($tx, $ec);
//        $signed->sign(0, $key, new WaspTransactionOutput($satoshi, $scriptPubKey));
//        echo $signed->get()->getHex().PHP_EOL;
//
//        //$buffer = Buffer::hex('6a084f41010001890656753d68747470733a2f2f73332d61702d6e6f727468656173742d312e616d617a6f6e6177732e636f6d2f70692d696d672f33615934765a4c55546e6b4a4a4c534b463671593435454d534d57424a4b367643776f6643');
//        //$parsedScript = MarkerOutput::deserializePayload(MarkerOutput::parseScript($buffer));
//        $tx = TransactionFactory::fromHex(Buffer::hex('010000000150e6cdea4c05d25e813bea575fd7da3127b2260e20eb95f3b5501671de87b6e3000000008a47304402202e928c6463a91dc0d12f99ce504003a772a5cde76204bc26dca87da7eb374191022029c07d7b3344dc869ec4c24db76ad2d8c2084c0038cda74ed1d50b93f371e75d014104644d7e279a9c614f4f0d1fc1665f313a02daa0825aaeb541cc8bc95778696b478ce5a3fe16affcb8ca8e2ee283d8434593cbfaef5a3f3c69205aad9faa4426ceffffffff0380f0fa02000000001976a91431031547a4e502a36b38641887b936505de547d988ac0000000000000000216a1f4f41010001e70717753d68747470733a2f2f676f6f2e676c2f6c3275583864c993e403000000001976a91431031547a4e502a36b38641887b936505de547d988ac00000000'));
//        $outputs = $tx->getOutputs();
//        $output1Buffer = $outputs[1]->getScript()->getBuffer();
//        $parsedScript = MarkerOutput::parseScript($output1Buffer);
//        $markerOutput = MarkerOutput::deserializePayload($parsedScript);
//    }
}
