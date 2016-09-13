<?php

namespace OKohei\OpenAssets\Protocols;

use OKohei\OpenAssets\Protocols\HttpAssetDefinitionLoader;

class AssetDefinitionLoader
{
    public $loader;

    public function __construct($metadata)
    {
        if (!filter_var($metadata, FILTER_VALIDATE_URL) === false) {
            $this->loader = new HttpAssetDefinitionLoader($metadata);
        }
    }

    public function loadDefinition()
    {
        if (!$this->loader) {
            return null;
        }
        return $this->loader->load();
    }
}
