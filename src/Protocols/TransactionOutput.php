<?php

namespace OKohei\OpenAssets\Protocols;

use OKohei\OpenAssets\Protocols\OutputType;
use OKohei\OpenAssets\Protocols\MarkerOutput;
use OKohei\OpenAssets\Util;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Amount;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptInfo\Multisig;
use Exception;

class TransactionOutput
{   
    public $value;         
    public $script;        
    public $assetId;       
    public $assetQuantity;
    public $outputType;

    public $account;
    public $metadata;
    public $assetDefinitionUrl;
    public $assetDefinition;
    
    public function __construct($value, Script $script, $assetId = null, $assetQuantity = 0, $outputType = OutputType::UNCOLORED, $metadata = null)
    {
        if (!OutputType::isLabel($outputType)) {
            throw new Exception ('invalid output type');
        }
        
        if ($assetQuantity < 0 && $assetQuantity >= MarkerOutput::MAX_ASSET_QUANTITY) {
            throw new Exception ('invalid output type');
        }
        $this->value = $value;
        $this->script = $script;
        $this->assetId = $assetId;
        $this->assetQuantity = $assetQuantity;
        $this->outputType = $outputType;
        $this->metadata = $metadata;
        $this->getLoadAssetDefinitionUrl();
    }

    public function getAssetAmount()
    {
        $divisibility = $this->getDivisibility();
        return $divisibility > 0 ? ($this->assetQuantity / (10 ** $divisibility)) : $this->assetQuantity;
    }
    
    public function getDivisibility()
    {
        if (!$this->validAssetDefinition() || is_null($this->assetDefinition->divisibility)) {
            return 0;
        }
        return $this->assetDefinition->divisibility;
    }
    
    public function getProofOfAuthenticity()
    {
        return $this->validAssetDefinition() ? $this->assetDefinition->proofOfAuthenticity : false;
    }

    public function getAssetId()
    {
        return $this->assetId;
    }
    
    public function getScript()
    {
        return $this->script;
    }
    
    public function getAssetQuantity()
    {
        return $this->assetQuantity;
    }
    
    public function getValue()
    {
        return $this->value;
    }

    public function toHash()
    {
        $amount = new Amount();
        return [
            'address' =>  $this->getAddress(),
            'oa_address' => $this->getOaAddress(),
            'script' => $this->script->getBuffer()->getHex(),
            'amount' => $amount->toBtc($this->value),
            'asset_id' => $this->assetId,
            'asset_quantity' => $this->assetQuantity,
            'asset_amount' => $this->getAssetAmount(),
            'account' => $this->account,
            'asset_definition_url' => $this->assetDefinitionUrl,
            'proof_of_authenticity' => $this->getProofOfAuthenticity(),
            'output_type' => OutputType::outputTypeLabel($this->outputType)
        ];
    }

    public function getAddress()
    {
        $classifier = new OutputClassifier();
        if ($classifier->isMultisig($this->script)) {
            $handler = new Multisig($this->script);
            foreach ($handler->getKeys() as $address) {
                if ($address == null) {
                    return null;
                }
            }
        }
        return Util::scriptToAddress($this->script);
    }

    public function getOaAddress()
    {
        $oaAddress = $this->getAddress();
        if (is_null($oaAddress)) {
            return null;
        }

        if (is_array($oaAddress)) {
            $res = [];
            foreach ($oaAddress as $obj) {
                $res[] = Util::toOaAddress($obj);
            }
            return $res;
        }
        return Util::toOaAddress($oaAddress);
    }

    public function getLoadAssetDefinitionUrl()
    {
        $this->assetDefinitionUrl = null;
        if (!$this->metadata || strlen($this->metadata) == 0) {
            return null;
        }
        $prefix = 'u=';
        if (substr($this->metadata,  0, strlen($prefix)) === $prefix) {
            $metadataUrl = $this->getMetadataUrl();
            $this->assetDefinition = $this->loadAssetDefinition($metadataUrl);
            if ($this->validAssetDefinition()) {
                $this->assetDefinitionUrl = $metadataUrl;
            } else {
                $this->assetDefinitionUrl = "The asset definition is invalid. $metadataUrl";
            }
        } else {
            $this->assetDefinitionUrl = 'Invalid metadata format.';
        }
        return $this->assetDefinitionUrl;
    }

    public function getMetadataUrl()
    {
        if ($this->metadata) {
            return substr($this->metadata, 2);
        }
        return null;
    }

    public function validAssetDefinition()
    {
        if (is_null($this->assetDefinition)){
            return false;
        } 
        return $this->assetDefinition->hasAssetId($this->assetId);
    }

    public function loadAssetDefinition($url)
    {
        $loader = new AssetDefinitionLoader($this->getMetadataUrl());
        return $loader->loadDefinition();
    }
}
