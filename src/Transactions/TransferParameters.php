<?php

namespace OKohei\OpenAssets\Transactions;

class TransferParameters 
{
    public $unspentOutputs;
    public $amount;
    public $changeScript;
    public $toScript;
    public $outputQty;

     /**
     * TransferParameters constructor.
     * @param array SpendableOutput $unspentOutputs
     * @param string $toScript
     * @param string $changeScript
     * @param int $amount
     */
    public function __construct($unspentOutputs, $toScript, $changeScript, $amount, $outputQty = 1)
    {
        $this->unspentOutputs = $unspentOutputs;
        $this->toScript = $toScript;
        $this->changeScript = $changeScript;
        $this->amount = $amount;
        $this->outputQty = $outputQty;
    }

     /**
     * splitOutputAmount constructor.
     * @return array $splitAmounts
     */
    public function splitOutputAmount()
    {
        $splitAmounts = [];
        for ($i = 0; $i <= $this->outputQty -1 ; $i++) {
          if ($i == $this->outputQty - 1) {
            $value = $this->amount / $this->outputQty + $this->amount % $this->outputQty;
          } else {
            $value = $this->amount / $this->outputQty;
          }
          $splitAmounts[] = $value;
        }
        return $splitAmounts;
    }
}
