<?php
namespace OKohei\OpenAssets\Transactions;

use OKohei\OpenAssets\Transactions\TransferParameters;
use OKohei\OpenAssets\Protocols\MarkerOutput;
use OKohei\OpenAssets\Util;

use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Transaction\OutPoint as WaspOutPoint;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use Exception;

class TransactionBuilder
{
    public $amount;
    public $efr; //an estimated transaction fee rate (satoshis/KB).

    public function __construct($amount = 600, $efr = 10000)
    {
        $this->amount = $amount;
    }
     /**
     * Creates a transaction for issuing an asset.
     * @param [TransferParameters] issue_spec The parameters of the issuance.
     * @param [bytes] metadata The metadata to be embedded in the transaction.
     * @param [Integer] fees The fees to include in the transaction.
     */
    public function issueAsset(TransferParameters $issueSpec, $metadata, $fees = null)
    {
        if (is_null($fees)) {
            $fees = $this->calcFee(1, 4);           
        }

        $tx = TransactionFactory::build();
        
        $uncoloredOutputs = self::collectUncoloredOutputs($issueSpec->unspentOutputs, $this->amount * 2 + $fees);
        $inputs = $uncoloredOutputs[0];
        $totalAmount = $uncoloredOutputs[1];

        foreach ($inputs as $input) {
            $tx = $tx->spendOutPoint($input->getWaspOutPoint(), $input->output()->getScript());
        }

        $issueAddress  = Util::oaAddressToBtcAddress($issueSpec->toScript);
        $fromAddress  = Util::oaAddressToBtcAddress($issueSpec->changeScript);
        if (AddressFactory::isValidAddress($issueAddress) == false || AddressFactory::isValidAddress($fromAddress) == false) {
            return false;
        }
        $assetQuantities = [];
        foreach ($issueSpec->splitOutputAmount() as $amount) {
            $assetQuantities[] = $amount;
            $tx = $tx->payToAddress($this->amount, AddressFactory::fromString($issueAddress)); //getcoloredoutput
        }

        $tx = $tx->outputs([self::getMarkerOutput($assetQuantities, $metadata)]); //getcoloredoutput
        $tx = $tx->payToAddress($totalAmount - $this->amount - $fees, AddressFactory::fromString($fromAddress)); //getuncoloredoutput
        $tx = $tx->get();
        return $tx;
    }

    public function transferAsset($assetId, $assetTransferSpec, $btcChangeScript, $fees)
    {
        $btcTransferSpec = new transferparameters($assetTransferSpec->unspentOutputs, 
            null, 
            Util::oaAddressToBtcAddress($btcChangeScript), 
            0
        );
        return $this->transfer([[$assetId, $assetTransferSpec]], [$btcTransferSpec], $fees); 
    }
    
    public function transferAssets($transferSpecs, $btcTransferSpec, $fees)
    {
        return $this->transfer($transferSpecs, [$btcTransferSpec], $fees);
    }

    public function transferBtc($btcTransferSpec, $fees)
    {
        return $this->transferBtcs([$btcTransferSpec], $fees);
    }
    
    public function transferBtcs($btcTransferSpecs, $fees)
    {
        return $this->transfer([], $btcTransferSpec, $fees);
    }


    public function burnAsset($unspents, $assetId, $fee)
    {
        //ToDo::
    }

    # collect uncolored outputs in unspent outputs(contains colored output).
    # @param array OpenAssets\Transaction\SpendableOutput $unspentOutputs :The Array of available outputs.
    # @param integer $amount :The amount to collect.
    # @return array [inputs, totalAmount]
    public static function collectUncoloredOutputs($unspentOutputs, $amount )
    {
        $totalAmount = 0;
        $results = [];
        foreach($unspentOutputs as $unspentOutput) {
            if (is_null($unspentOutput->output()->getAssetId())) {
                $results[] = $unspentOutput;
                $totalAmount += $unspentOutput->output()->getValue();
            }
            if ($totalAmount >= $amount) {
                return [$results, $totalAmount];
            } 
        }
        throw new Exception('Collect Uncolored Outputs went to Wrong');
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
        foreach($unspentOutputs as $unspentOutput) {
            if ($unspentOutput->output()->getAssetId() == $assetId) {
                $results[] = $unspentOutput;
                $totalAmount += $unspentOutput->output()->getAssetQuantity();
            }
            if ($totalAmount >= $assetQuantity) {
                return [$results, $totalAmount];
            } 
        }
        throw new Exception('Collect Colored Outputs went to Wrong');
    }

    # create marker output.
    # @param [Array] asset_quantities asset_quantity array.
    # @param [String] metadata
    # @return [Bitcoin::Protocol::TxOut] the marker output.
    public static function getMarkerOutput($assetQuantities, $metadata = null)
    {
        $markerOutput = new MarkerOutput($assetQuantities, $metadata);
        return new TransactionOutput(0, $markerOutput->buildScript());
    }
    
    # create marker output.
    # @param [Array] asset_quantities asset_quantity array.
    # @param [String] metadata
    # @return [Bitcoin::Protocol::TxOut] the marker output.
    public function getColoredOutput($address)
    {
        $address = AddressFactory::fromString($address);
        return new TransactionOutput($this->amount, ScriptFactory::scriptPubKey()->payToAddress($address));
    }
    
    # create marker output.
    # @param [Array] asset_quantities asset_quantity array.
    # @param [String] metadata
    # @return [Bitcoin::Protocol::TxOut] the marker output.
    public function getUncoloredOutput($address, $value)
    {
        if ($value < $this->amount) {
            throw new Exception('DustOutputError');
        }
        $address = AddressFactory::fromString($address);
        return new TransactionOutput($value, ScriptFactory::scriptPubKey()->payToAddress($address));
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

    public function transfer($assetTransferSpecs, $btcTransferSpecs, $fees)
    {
        $inputs = []; //vin
        $outputs = []; //vout
        $assetQuantities = [];

        //Only when assets are transfered
        $assetBasedSpecs = [];
        foreach($assetTransferSpecs as $spec) {
            $assetId = $spec[0];
            $transferSpec = $spec[1];
            if (!array_key_exists($assetId, $assetBasedSpecs)) {
                $assetBasedSpecs[$assetId] = [];
            }
            if (!isset($assetBasedSpecs->changeScript) || !array_key_exists($assetId, $assetBasedSpecs->changeScript)) {
                $assetBasedSpecs[$assetId][$transferSpec->changeScript] = [];
            }
            $assetBasedSpecs[$assetId][$transferSpec->changeScript][] = $transferSpec;
        }

        foreach ($assetBasedSpecs as $assetId => $addressBasedSpecs) {
            foreach ($addressBasedSpecs as $transferSpecs) {
                $transferAmount = 0;
                foreach($transferSpecs as $transferSpec) {
                    $transferAmount += $transferSpec->amount;
                }

                $ret = self::collectColoredOutputs($transferSpecs[0]->unspentOutputs, $assetId, $transferAmount);
                $coloredOutputs = $ret[0];
                $totalAmount = $ret[1];
                foreach($coloredOutputs as $coloredOutput) {
                    $inputs[] = $coloredOutput;
                }
                foreach($transferSpecs as $spec) {
                    foreach($spec->splitOutputAmount() as $amount) {
                        $outputs[] = self::getColoredOutput(Util::oaAddressToBtcAddress($spec->toScript));
                        $assetQuantities[] = $amount;
                    }
                }

                if ($totalAmount > $transferAmount) {
                    $outputs[] = self::getColoredOutput(Util::oaAddressToBtcAddress($transferSpecs[0]->changeScript));
                    $assetQuantities[] = $totalAmount - $transferAmount;
                }
            }
        }
        //End of asset settings
        
        ## For bitcoins transfer
        # Assume that there is one address from
        $utxo = $btcTransferSpecs[0]->unspentOutputs; //check cloned
        # Calculate rest of bitcoins in asset settings
        # btc_excess = inputs(colored) total satoshi - outputs(transfer) total satoshi
        $btcExcessInput = 0;
        $btcExcessOutput = 0;
        foreach($inputs as $input) {
            $btcExcessInput += $input->output->getValue();
        }
        foreach ($outputs as $output) {
            $btcExcessOutput += $output->getValue(); 
        }
        $btcExcess = $btcExcessInput - $btcExcessOutput;
        # Calculate total amount of bitcoins to send
        $btcTransferTotalAmount = 0;
        foreach ($btcTransferSpecs as $btcTransferSpec) {
            $btcTransferTotalAmount += $btcTransferSpec->amount;
        }
        if (is_null($fees)) {
            $fixedFees = 0;
        } else {
            $fixedFees = $fees;
        }
        
        if ($btcExcess < ($btcTransferTotalAmount + $fixedFees)) {
          # When there does not exist enough bitcoins to send in the inputs
          # assign new address (utxo) to the inputs (does not include output coins)
            # CREATING INPUT (if needed)
            $ret = self::collectUncoloredOutputs($utxo, $btcTransferTotalAmount + $fixedFees - $btcExcess);
            $uncoloredOutputs = $ret[0];
            $uncoloredAmount = $ret[1];
            foreach ($uncoloredOutputs as $uncoloredOutput) {
                if(($key = array_search($uncoloredOutput, $utxo)) !== false) {
                    unset($utxo[$key]);
                }
                $inputs[] = $uncoloredOutput;
            }
            $btcExcess += $uncoloredAmount;
        }

        # Calculate fees and otsuri (the numbers of vins and vouts are known)
        # +1 in the second term means "otsuri" vout, 
        # and outputs size means the number of vout witn asset_id
        if (is_null($fees)) {
          $fees = $this->calcFee(count($inputs), count($outputs) + count($btcTransferSpecs) + 1);
        }
        
        $otsuri = $btcExcess - $btcTransferTotalAmount - $fees;
        
        if ($otsuri > 0 && $otsuri < $this->amount) {
            # When there exists otsuri, but it is smaller than @amount (default is 600 satoshis)
            # assign new address (utxo) to the input (does not include @amount - otsuri)
            # CREATING INPUT (if needed)
            $res =  self::collectUncoloredOutputs($utxo, $this->amount - $otsuri);
            $uncoloredOutputs = $res[0];
            $uncoloredAmount = $res[1];
            foreach ($uncoloredOutputs as $uncoloredOutput) {
                $inputs[] = $uncoloredOutput;
            }
            $otsuri += $uncoloredAmount;
        }
        
        if ($otsuri > 0) {
            # When there exists otsuri, write it to outputs
            # CREATING OUTPUT
            $outputs[] = self::getUncoloredOutput($btcTransferSpecs[0]->changeScript, $otsuri);
        }
        
        foreach ($btcTransferSpecs as $btcTransferSpec) {
            if ($btcTransferSpec->amount > 0) {
                # Write output for bitcoin transfer by specifics of the argument
                # CREATING OUTPUT
                foreach ($btcTransferSpec->splitOutputAmount() as $amount) {
                  $outputs[] = slef::getUncoloredOutput($btcTransferSpec->toScript, $amount);
                }
            }
        }
    
        if (!empty($assetQuantities)) {
            array_unshift($outputs, self::getMarkerOutput($assetQuantities));
        }
        
        $tx = TransactionFactory::build();
        foreach(Util::arrayFlatten($inputs) as $input) {
            $tx = $tx->spendOutPoint($input->getWaspOutPoint(), $input->output()->getScript());
        }
        $tx = $tx->outputs($outputs);
        return $tx->get();
    }

    public static function testBitwasp($tx)
    {
        $ec = Bitcoin::getEcAdapter();
        $key = PrivateKeyFactory::fromWif('cVxfm7SsbtAGcL5zhP6aJVbRBVJLiCS2i4EGnL64AJSm7HervuyN');
        $outpoint = new WaspOutPoint(Buffer::hex($tx), 0);
        $scriptPubKey = ScriptFactory::scriptPubKey()->payToPubKeyHash($key->getPublicKey());
        $value = 10000;
        $txOut = new TransactionOutput($value, $scriptPubKey);
        $tx = TransactionFactory::build();
        $tx = $tx->spendOutPoint($outpoint);
        $tx = $tx->spendOutPoint($outpoint);
        $tx = $tx->payToAddress($value, AddressFactory::fromKey($key));
        $tx = $tx->payToAddress($value, AddressFactory::fromKey($key));
        $tx = $tx->get();
        return $tx;
        $signed = new Signer($tx, $ec);
        $signed->sign(0, $key, $txOut);
        return $signed->get()->getHex();
    }
}

