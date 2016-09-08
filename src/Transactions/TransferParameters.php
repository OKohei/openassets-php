<?php

namespace OKohei\OpenAssets\Transactions;

class TransferParameters 
{
    private $unspentOutputs;
    private $amount;
    private $changeScript;
    private $toScript;
    private $outputQty;

     /**
     * TransferParameters constructor.
     * @param SpendableOutput $unspentOutputs
     * @param string $toScript
     * @param string $changeScript
     * @param int $amount
     */
    public function TransferParameters($unspentOutputs, $toScript, $changeScript, $amount, $outputQty = 1)
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
        $splitAmounts = []
        for ($i = 0; $i <= $outputQty; $i++)
          if ($i == $outputQty - 1) {
            $value = $this->amount / $outputQty + $this->amount % $outputQty;
          } else {
            $value = $amount / $outputQty;
          }
          $splitAmounts[] = $value;
        }
        return $splitAmounts;
    }
}
