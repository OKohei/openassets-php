<?php

namespace OKohei\OpenAssets\Protocols;

use OKohei\OpenAssets\Util;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\Opcodes;

use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Types\VarInt;

class MarkerOutput
{
    const MAX_ASSET_QUANTITY = 2 ** 63 -1;
    const OAP_MARKER = "4f41";
    const VERSION = "0100";

    protected $assetQuantities;
    protected $metadata;

    public function __construct($assetQuantities, $metadata)
    {
        $this->assetQuantities = $assetQuantities;
        $this->metadata = $metadata;
    }

    public function getAssetQuantities()
    {
        return $this->assetQuantities;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function buildScript()
    {
        $buffer = Buffer::hex($this->serializePayload());
        return ScriptFactory::sequence([Opcodes::OP_RETURN, $buffer]);
    }

    public function serializePayload()
    {
        $payload = [self::OAP_MARKER, self::VERSION];
        $buffer = Buffertools::numToVarInt(count($this->assetQuantities));
        $payload[] = self::getSortHex($buffer);
        foreach($this->assetQuantities as $quantity) {
            $payload[] = Util::encodeLeb128($quantity)[1];
        }
        $buffer = Buffertools::numToVarInt(strlen($this->metadata));
        $payload[] = self::getSortHex($buffer);
        $tmp = null;
        $metaBuffer = new Buffer($this->metadata ?? '');
        $payload[] = $metaBuffer->getHex();
        return implode('', $payload);
    }

    public static function deserializePayload($payload)
    {
        if (self::valid($payload) !== true) {
            return null;
        }
        //remove OA bytes
        $payload = substr($payload, strlen(self::OAP_MARKER.self::VERSION));
        
        $parsedAssetQty = self::parseAssetQty($payload);
        $assetQuantity = $parsedAssetQty[0];
        $payload = $parsedAssetQty[1];
        $base = null;
        
        foreach(str_split($payload, 2) as $byte) {
            $base .= Buffer::hex($byte)->getInt() >= 128 ? $byte : $byte.'|';
        }
        
        $base = substr($base, 0, -1); //remove last '|' 
        $data = explode('|', $base);
        $list = implode(array_slice($data, 0, $assetQuantity));
        
        $assetQuantities = Util::decodeLeb128($list);
        $metaHex = Buffer::hex($payload)->slice(Buffer::hex($list)->getSize() + 1);
        $metadata = empty($metaHex) ? NULL : $metaHex->getBinary();
        return new MarkerOutput($assetQuantities, $metadata);
        
    }

    public static function parseScript(Buffer $buf)
    {
        $script = ScriptFactory::create($buf)->getScript();
        $parse = $script->getScriptParser()->decode();
        if ($parse[0]->getOp() == Opcodes::OP_RETURN) {
            $hex = $parse[1]->getData()->getHex();
            return self::valid($hex) ? $hex : null;
        } else {
            return null;
        }
    }

    private static function parseAssetQty($payload)
    {
        $buffer = Buffer::hex($payload);
        switch ($buffer->slice(0,1)->getHex()) {
        case "fd":
            return [$buffer->slice(1,2)->getInt(), $buffer->slice(3)->getHex()];
        case 'fe':
            return [$buffer->slice(1,4)->getInt(), $buffer->slice(5)->getHex()];
        default:
            return [$buffer->slice(0,1)->getInt(), $buffer->slice(1)->getHex()];
        }
    }
    
    public static function getSortHex(Buffer $buffer)
    {
        switch ($buffer->slice(0,1)->getHex()) {
        case 'fd':
            $newHex = $buffer->slice(0,1)->getHex().
                $buffer->slice(2,3)->getHex().
                $buffer->slice(1,1)->getHex();
            return Buffer::hex($newHex)->getHex();
        case 'fe':
            $newHex = $buffer->slice(0,1)->getHex().
                $buffer->slice(4,5)->getHex().
                $buffer->slice(3,4)->getHex().
                $buffer->slice(1,1)->getHex();
            return Buffer::hex($newHex)->getHex();
        default:
            return $buffer->getHex();
        }
    }
    
    public static function valid($data)
    {
        if (is_null($data)) {
            return false;
        }

        if (substr($data,  0, 8) !== self::OAP_MARKER.self::VERSION) {
            return false;
        }

        //ToDo:: readLeb128
        //ToDo:: readVarInteger
        return true;
    }
}
