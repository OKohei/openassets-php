<?php
namespace OKohei\OpenAssets\Tests\Protocols;

use OKohei\OpenAssets\Protocols\TransactionOutput;
use OKohei\OpenAssets\Protocols\MarkerOutput;
use OKohei\OpenAssets\Protocols\OutputType;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use Exception;
use Error;

class TransactionOutputTest extends \PHPUnit_Framework_TestCase
{
    public function testInitTransaction()
    {
        $buffer = Buffer::hex('abcd');
        $script = ScriptFactory::create($buffer)->getScript();
        $transactionOutput = new TransactionOutput(100, $script, 'ALn3aK1fSuG27N96UGYB1kUYUpGKRhBuBC', 9223372036854775807, OutputType::MARKER_OUTPUT);
        $this->assertEquals(OutputType::MARKER_OUTPUT, $transactionOutput->outputType);
        $this->assertEquals(9223372036854775807, $transactionOutput->assetQuantity);
        $this->assertEquals('ALn3aK1fSuG27N96UGYB1kUYUpGKRhBuBC', $transactionOutput->assetId);
        $this->assertEquals('abcd', $transactionOutput->script->getBuffer()->getHex());
    }
    
    public function testInvalidInitTransaction()
    {
        try {
            $buffer = Buffer::hex('');
            $script = ScriptFactory::create($buffer);
            $transactionOutput = new TransactionOutput(100, $script, 'ALn3aK1fSuG27N96UGYB1kUYUpGKRhBuBC', 9223372036854775807, 10);
        } catch (Error $e) {
            $this->assertTrue(true);
        }
    }
    
    public function testInvalidAssetQuantity()
    {
        try {
            $buffer = Buffer::hex('');
            $script = ScriptFactory::create($buffer);
            $transactionOutput = new TransactionOutput(100, $script, 'ALn3aK1fSuG27N96UGYB1kUYUpGKRhBuBC', 9223372036854775808, 10);
        } catch (Error $e) {
            $this->assertTrue(true);
        }
    }
    
    public function testParseMetaData()
    {
        $script = new Script(Buffer::hex(bin2hex('hoge')));
        $transactionOutput = new TransactionOutput(100, $script, 'ALn3aK1fSuG27N96UGYB1kUYUpGKRhBuBC', 200, OutputType::ISSUANCE);
        $this->assertNull($transactionOutput->assetDefinitionUrl);
        $transactionOutput = new TransactionOutput(100, $script, 'ALn3aK1fSuG27N96UGYB1kUYUpGKRhBuBC', 200, OutputType::ISSUANCE, 'hoge');
        $this->assertEquals('Invalid metadata format.', $transactionOutput->assetDefinitionUrl);
        $transactionOutput = new TransactionOutput(100, $script, 'ALn3aK1fSuG27N96UGYB1kUYUpGKRhBuBC', 200, OutputType::ISSUANCE, 'u=http://goo.gl/fS4mEj');
        $this->assertEquals('The asset definition is invalid. http://goo.gl/fS4mEj', $transactionOutput->assetDefinitionUrl);
        $transactionOutput = new TransactionOutput(100, $script, 'AJk2Gx5V67S2wNuwTK5hef3TpHunfbjcmX', 200, OutputType::ISSUANCE, 'u=http://goo.gl/fS4mEj');
        $this->assertEquals('http://goo.gl/fS4mEj', $transactionOutput->assetDefinitionUrl);
        $transactionOutput = new TransactionOutput(100, $script, 'AGHhobo7pVQN5fZWqv3rhdc324ryT7qVTB', 200, OutputType::ISSUANCE, 'u=http://goo.gl/Q0NZfe');
        $this->assertEquals(0, $transactionOutput->getDivisibility());
        $this->assertEquals(200, $transactionOutput->getAssetAmount());
    }
    
    public function testToHash()
    {
        $testnet = NetworkFactory::bitcoinTestnet();
        Bitcoin::setNetwork($testnet);
        $issueSpec = TransactionFactory::fromHex(Buffer::hex('010000000154f5a67cb14d7e50056f53263b72165daaf438164e7e825b862b9062a4e40612000000006b48304502210098e16e338e9600876e30d9dc0894bcd1bbb612431e7a36732c5feab0686d0641022044e7dcd512073f31d0c67e0fbbf2269c4a31d5bf3bb1fcc8fbdd2e4d3c0d7e58012103e46fdcbf2062598a221c0e34d0505c270fb77c2c305c40ef0919f8efc0c7f959ffffffff0358020000000000001976a91438a6ebdf20cae2c9287ea014464042112ea3dbfd88ac0000000000000000216a1f4f410100018f4e17753d68747470733a2f2f676f6f2e676c2f755667737434b8770700000000001976a91438a6ebdf20cae2c9287ea014464042112ea3dbfd88ac00000000'));
        $transferSpec = TransactionFactory::fromHex(Buffer::hex('0100000002dd6cee22d848a609df2d316112ca26b569c97c189400ad6f01046d65aa7b5f52000000006a473044022021806c9f0d888862cb6e8eb3952c48499fe4c0bedc4fb3ef20743c418109a23b02206249fceeeb4c2f496a3a48b57087f97e540af465f8b9328919f6f536ba5346ed012103e46fdcbf2062598a221c0e34d0505c270fb77c2c305c40ef0919f8efc0c7f959ffffffffdd6cee22d848a609df2d316112ca26b569c97c189400ad6f01046d65aa7b5f52020000006b483045022100981c9757ddf1280a47e9274fae9ff331a1a5b750c7f0c2a18de0b18413a3121e0220395d8baeb7802f9f3947152098442144946987d6be4065a0febe20bc20ca55df012103e46fdcbf2062598a221c0e34d0505c270fb77c2c305c40ef0919f8efc0c7f959ffffffff0400000000000000000b6a094f4101000263ac4d0058020000000000001976a914e9ac589641f17a2286631c24d6d2d00b8c959eb588ac58020000000000001976a91438a6ebdf20cae2c9287ea014464042112ea3dbfd88ac504e0700000000001976a91438a6ebdf20cae2c9287ea014464042112ea3dbfd88ac00000000'));

        $outputs = $issueSpec->getOutputs();
        $output1Buffer = $outputs[1]->getScript()->getBuffer();
        $parsedScript = MarkerOutput::parseScript($output1Buffer);
        $markerOutput = MarkerOutput::deserializePayload($parsedScript);

        $transferSpecScript = $transferSpec->getOutputs()[2]->getScript();
        $transactionOutput = new TransactionOutput(600, $transferSpecScript, 'oK31ByjFuNhfnFuRMmZgchsdiprYmRzuz5', 9900, OutputType::TRANSFER, $markerOutput->getMetadata());
        $transactionOutput->account = 'hoge';
        $hash = $transactionOutput->toHash();
        $expectedHash = [
            'address' => 'mkgW6hNYBctmqDtTTsTJrsf2Gh2NPtoCU4',
            'oa_address' => 'bWvePLsBsf6nThU3pWVZVWjZbcJCYQxHCpE',
            'script' => '76a91438a6ebdf20cae2c9287ea014464042112ea3dbfd88ac',
            'amount' => '0.00000600',
            'asset_id' => 'oK31ByjFuNhfnFuRMmZgchsdiprYmRzuz5',
            'asset_quantity' => 9900,
            'asset_amount' => 990,
            'account' => 'hoge',
            'asset_definition_url' => 'https://goo.gl/uVgst4',
            'proof_of_authenticity' => false,
            'output_type' => 'transfer'
            ];
        $this->assertEquals($expectedHash, $hash);
    }

    public function testToHashWithMultisig()
    {
        $testnet = NetworkFactory::bitcoinTestnet();
        Bitcoin::setNetwork($testnet);
        $tx = TransactionFactory::fromHex(Buffer::hex('0100000001aac9ccdb7eb7a47a35be4e1814c675658fd6de41d1747c8000e6ce09b9faa221000000006b483045022100a94d970535e58ca5e8df01e671806fb1d7bb2157492e8ec1b5dc7bc70c4cfb65022055a94e8679d0ad75b09c948efe8b8132d2cc5cc9a38a588cd1d8cd84e03c1cb50121030712171ff2a109f94ec422b9830c456a3c1f97eec253a0f09f024b50a895e3d8ffffffff02b0c0d617000000001976a9146409eb200880acae69f3458591c3a7f36c4c770288ac80f0fa020000000047522103cdd34ec0a05d91c026fe8cb74434923075d3acc20f3f673fb855c8f2c04ca5222103b99b5e2a06b41612a6235c0a536fabfd293d4fce5fe6a4ba3461ed6f07d5372052ae00000000'));
        $output = $tx->getOutputs()[1];
        $transactionOutput = new TransactionOutput($output->getValue(), $output->getScript());
        $hash = $transactionOutput->toHash();
        $count = count($transactionOutput->getAddress());
        $this->assertEquals(2, $count);
        $this->assertEquals(['mx8JNZiqmTEG7KcrL1PtWuAzU6adagE8V6', 'mzURevsZS7FZnBBuBVyCGrG2oRtWS9ArxV'], $hash['address']);
    }
    
    public function testNotStandardOutput()
    {
        $tx = TransactionFactory::fromHex(Buffer::hex('01000000018177482b65ec42fc43c6b2ad13955d7fdec00edb5dc5ac483d9e31eb06a5a5d5010000006c493046022100955062369843b52db91eb9c1b8fb5ed20b346a62841edfb2ba2097d2a9bc31810221009ace1c91398620b4d1bfa559ca2abcaf6c1a524e606bb5fedf74c9a123ae4ec8012103046d258651af2fbb6acb63414a604314ce94d644a0efd8832ca5275f2bc207c6ffffffff05404b4c0000000000475221033423007d8f263819a2e42becaaf5b06f34cb09919e06304349d950668209eaed21021d69e2b68c3960903b702af7829fadcd80bd89b158150c85c4a75b2c8cb9c39452ae404b4c00000000002752010021021d69e2b68c3960903b702af7829fadcd80bd89b158150c85c4a75b2c8cb9c39452ae404b4c00000000004752210279be667ef9dcbbac55a06295ce870b07029bfcdb2dce28d959f2815b16f8179821021d69e2b68c3960903b702af7829fadcd80bd89b158150c85c4a75b2c8cb9c39452aeb0f0c304000000001976a9146cce12229300b733cdf0c7ce3079c7503b080fca88ac404b4c000000000047522102c6047f9441ed7d6d3045406e95c07cd85c778e4b8cef3ca7abac09b95c709ee521021d69e2b68c3960903b702af7829fadcd80bd89b158150c85c4a75b2c8cb9c39452ae00000000'));
        $output = $tx->getOutputs()[1];
        $transactionOutput = new TransactionOutput($output->getValue(), $output->getScript());
        $this->assertNull($transactionOutput->getAddress());
        $this->assertNull($transactionOutput->getOaAddress());
    }
}
