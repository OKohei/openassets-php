<?php

namespace OKohei\OpenAssets\Protocols;

class MarkerOutput
{

    public function __construct($assetQuantities, $metadata)
    {
        $this->assetQuantities = $assetQuantities;
        $this->metadata = $metadata;
    }

    public function assetQuantities()
    {
        return $this->assetQuantities;
    }

    public function metadata()
    {
        return $this->metadata;
    }

    public function deserializePayload()
    {
    }

    public function serializePayload()
    {
    }

    public static function parseScript()
    {
    }

    public static function buildScript()
    {
    }

    public static function leb128Decode()
    {
    }

    public static function leb128Encode()
    {
    }
}
