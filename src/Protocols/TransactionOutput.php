<?php

namespace OKohei\OpenAssets\Protocols;

class TransactionOutput
{   
    private $value;         
    private $script;        
    private $assetId;       
    private $assetQuantity;
    private $outputType;

    private $account;
    private $metadata;
    private $asset_definition_url;
    private $asset_definition;

    public function TransactionOutput($value = -1, $script, $asset_id = null, $assetQuantity = 0,$outputType = 'uncolored')
    {
        $this->value = $value;
        $this->script = $script;
        $this->assetId = $assetId;
        $this->assetQuantity = $assetQuantity;
        $this->outputType = $outputType;
    }

    public function value()
    {
    }
    
    public function script()
    {
    }
    
    public function assetId()
    {
    }

    public function assetQuantity()
    {
    }

    public function outputType()
    {
    }
}
