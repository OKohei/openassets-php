<?php

namespace OKohei\OpenAssets\Transactions;

use OKohei\OpenAssets\Transactions\OutPoint;
use OKohei\OpenAssets\Protocols\TransactionOutput;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Transaction\OutPoint as WaspOutPoint;

class SpendableOutput 
{   
    /**
     * @var OutPoint
     */
    public $outPoint;
    
    /**
     * @var TransactionOutput
     */
    public $output;
    
    /**
     * @var Integer 
     */
    public $confirmations;
    
    /**
     * @var String 
     */
    public $spendable;
    
    /**
     * @var String 
     */
    public $solvable;

    /**
     * @param OutPoint $outPoint
     * @param TransactionOutput $output
     * @return SpendableOutput
     */
    public function __construct(OutPoint $outPoint, TransactionOutput $output)
    {
        $this->outPoint = $outPoint;
        $this->output = $output;
        $this->confirmations = null;
        $this->spendable = null;
        $this->solvable = null;
    }

    /**
     * @return OutPoint
     */
    public function outPoint()
    {
        return $this->outPoint;
    }
    
    /**
     * @return WaspOutPoint
     */
    public function getWaspOutPoint()
    {
        return new WaspOutPoint(Buffer::hex($this->outPoint->hash), $this->outPoint->index);
    }

    /**
     * @return Transactionoutput
     */
    public function output()
    {
        return $this->output;
    }

    /**
     * @return array 
     */
    public function toHash()
    {
        if ($this->outPoint == null) {
            return [];
        }
        $hash =  [
            'txid' => $this->outPoint->hash,
            'vout' => $this->outPoint->index,
            'confirmations' => $this->confirmations,
            ];
        if ($this->solvable) {
            $hash['solvable'] = $this->solvable;
        }
        if ($this->spendable) {
            $hash['spendable'] = $this->spendable;
        }
        return array_merge($this->output->toHash(), $hash);
    }

    /**
     * @return String 
     */
    public function getTxId()
    {
        return ScriptFactory::create(Buffer::hex($this->outPoint->hash))->getScript();
    }
}
