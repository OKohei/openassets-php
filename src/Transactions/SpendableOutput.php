<?php

namespace OKohei\OpenAssets\Transactions;

use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\OutPoint;

class SpendableOutput 
{   
    protected $outPoint;
    protected $output;
    protected $confirmations;

    public function __construct(OutPoint $outPoint, TransactionOutput $output)
    {
        $this->outPoint = $outPoint;
        $this->output = $output;
        $this->confirmations = null;
    }

    public function outPoint()
    {
        return $this->outPoint;
    }

    public function output()
    {
        return $this->output;
    }

    public function toHash()
    {
        if ($this->outPoint == null) {
            return [];
        }
        return [
            'txid' => $this->outPoint->getTxId(),
            'vout' => $this->outPoint->getVout(),
            'confirmations' => $this->confirmations
        ];
    }
}
