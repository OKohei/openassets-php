<?php

namespace OKohei\OpenAssets\Transactions;

class TransactionBuilder
{
    private $amount;
    private $efr; //an estimated transaction fee rate (satoshis/KB).

    public function __construct($amount, $efr)
    {
        $this->amount = $amount;
    }
     /**
     * TransferParameters constructor.
     * @param SpendableOutput $unspentOutputs
     * @param string $toScript
     * @param string $changeScript
     * @param int $amount
     */
    public function issueAsset($issueSpec, $metadata, $fees)
    {
        if (is_null($fees)) {
            $fee = $this->calcFee(1, 4);           
        }
    }

    public function transferAsset($assetTransferSpecs, $btcTransferSpec, $fees)
    {
    }
    
    public function transferAssets($assetTransferSpecs, $btcTransferSpec, $fees)
    {
    }

    public function transferBtc()
    {
    }
    
    public function transferBtcs()
    {
    }


    public function btcAssetSwap()
    {
    }

    public function AssetAssetSwap()
    {
    }

    # collect uncolored outputs in unspent outputs(contains colored output).
    # @param array OpenAssets\Transaction\SpendableOutput $unspentOutputs :The Array of available outputs.
    # @param integer $amount :The amount to collect.
    # @return array [inputs, totalAmount]
    public static function collectUncoloredOutputs($spendableOutputs, $amount )
    {
        $totalAmount = 0;
        $results = [];
        foreach($spendableOutputs as $spendableOutput) {
            if (is_null($spendableOutput->output()->getAssetId())) {
                $results[] = $output;
                $totalAmount += $spendableOutput->output()->getValue();
            }
        }
        if ($totalAmount >= $amount) {
            return [$totalAmount, $amount];
        } else {
            throw new Exception('Something gets wrong');
        }
    }   
    
    # collect colored outputs in unspent outputs(contains colored output).
    # @param array OpenAssets\Transaction\SpendableOutput $unspentOutputs :The Array of available outputs.
    # @param integer $amount :The amount to collect.
    # @param integer $assetQuantity :The amount of assets.
    # @return array [inputs, totalAmount]
    public static function collectColoredOutputs($unspentOutputs, $assetId, $assetQuantity)
    {
        $totalAmount = 0;
        $results = [];
        foreach($spendableOutputs as $spendableOutput) {
            if ($spendableOutput->output()->getAssetId() == $assetId) {
                $results[] = $output;
                $totalAmount += $spendableOutput->output()->getAssetQuantity();
            }
        }
        if ($totalAmount >= $amount) {
            return [$totalAmount, $results];
        } else {
            throw new Exception('Something gets wrong');
        }
    }

    public static function getColoredOutput()
    {
    }

    public static function getMarkerOutput()
    {
    }

    # Calculate a transaction fee
    # @param integer $inputsNum: The number of vin fields.
    # @param integer $outputsNum: The number of vout fields.
    # @return integer :A transaction fee.
    public function calcFee($inputsNum, $outputsNum) 
    {
        $txSize = 148 * $inputsNum + 34 * $outputsNum + 10;
        return (1 + $txSize / 1000) * $this->efr;
    }
}

