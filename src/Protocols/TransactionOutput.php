<?php

namespace OKohei\OpenAssets\Protocols;

use OKohei\OpenAssets\Protocols\OutputType;
use OKohei\OpenAssets\Protocols\MarkerOutput;
use OKohei\OpenAssets\Util;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Amount;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptInfo\Multisig;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
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

    /**
     * @return asset amount based on divisibility 
     */
    public function getAssetAmount()
    {
        $divisibility = $this->getDivisibility();
        return $divisibility > 0 ? ($this->assetQuantity / (10 ** $divisibility)) : $this->assetQuantity;
    }
    
    /**
     * @return divisibility 
     */
    public function getDivisibility()
    {
        if (!$this->validAssetDefinition() || is_null($this->assetDefinition->divisibility)) {
            return 0;
        }
        return $this->assetDefinition->divisibility;
    }
    
    /**
     * @return proof of Authenticity 
     */
    public function getProofOfAuthenticity()
    {
        return $this->validAssetDefinition() ? $this->assetDefinition->proofOfAuthenticity : false;
    }

    /**
     * @return asset id 
     */
    public function getAssetId()
    {
        return $this->assetId;
    }
    
    /**
     * @return script 
     */
    public function getScript()
    {
        return $this->script;
    }
    
    /**
     * @return assetquantity 
     */
    public function getAssetQuantity()
    {
        return $this->assetQuantity;
    }
    
    /**
     * @return asset value 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return array of transactionoutput 
     */
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

    /**
     * @return btc address 
     */
    public function getAddress()
    {
        $classifier = new OutputClassifier();
        if ($classifier->isMultisig($this->script)) {
            $handler = Multisig::fromScript($this->script);
            $pubKeySerializer = EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, Bitcoin::getEcAdapter());
            foreach ($handler->getKeyBuffers() as $buffer) {
                $address = $pubKeySerializer->parse($buffer);
                if ($address == null) {
                    return null;
                }
            }
        }
        return Util::scriptToAddress($this->script);
    }

    /**
     * @return open assets address 
     */
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

    /**
     * Load metadata if data exists
     * @return  asset definition url
     */
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

    /**
     * @return meta data url 
     */
    public function getMetadataUrl()
    {
        if ($this->metadata) {
            return substr($this->metadata, 2);
        }
        return null;
    }

    /**
     * valudate asset definition 
     * @return boolean
     */
    public function validAssetDefinition()
    {
        if (is_null($this->assetDefinition)){
            return false;
        } 
        return $this->assetDefinition->hasAssetId($this->assetId);
    }

    /**
     * load asset definition 
     */
    public function loadAssetDefinition($url)
    {
        $loader = new AssetDefinitionLoader($this->getMetadataUrl());
        return $loader->loadDefinition();
    }
}
