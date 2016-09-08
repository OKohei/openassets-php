<?php

namespace OKohei\OpenAssets\Protocols;

class AssetDefinitionLoader
{

    public function __construct($assetQuantities, $metadata)
    {
        $this->assetQuantities = $assetQuantities;
        $this->metadata = $metadata;
    }
}
