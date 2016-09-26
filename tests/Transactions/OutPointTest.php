<?php
namespace OKohei\OpenAssets\Tests\Transactions;

use OKohei\OpenAssets\Transactions\OutPoint;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Network\NetworkFactory;
use Exception;

class OutPointTest extends \PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $testnet = NetworkFactory::bitcoinTestnet();
        Bitcoin::setNetwork($testnet);
    }
    
    public function testInitializeSuccess()
    {
        $outPoint = new OutPoint('8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8220', 0);
        $this->assertEquals('8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8220', $outPoint->hash);
        $this->assertEquals(0, $outPoint->index);
    }
    
    public function testInvalidTransactionHash()
    {
        try {
            $outPoint = new OutPoint('', 0);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testInvalidIndex()
    {
        try {
            $outPoint = new OutPoint("8a7e2adf117199f93c8515266497d2b9954f3f3dea0f043e06c19ad2b21b8220", -1);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }
}
