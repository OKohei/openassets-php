<?php

namespace OKohei\OpenAssets;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Base58;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Hash;

class Util 
{   
    const OA_NAMESPACE = 19;
    const OA_VERSION_BYTE = 23;
    const OA_VERSION_BYTE_TESTNET = 115;

    private static function getOaVersionByte() 
    {
        $network = Bitcoin::getNetwork();
        return $network->isTestnet() ? self::OA_VERSION_BYTE_TESTNET : self::OA_VERSION_BYTE; 
    }

    public static function toOaAddress($btcAddress)
    {
        $btcHex = Base58::decode($btcAddress);
        if ($btcHex->getSize() == 47) {
            $btcHex = '0'.$btcHex->getHex();
        } else {
            $btcHex = $btcHex->getHex();
        }
        $namedAddr = dechex(self::OA_NAMESPACE).substr($btcHex, 0, -8);
        $oaChecksum = Base58::checksum(Buffer::hex($namedAddr));
        return Base58::encode(Buffer::hex($namedAddr.$oaChecksum->getHex()));
    }

    public static function OaAddressToBtcAddress($oaAddress)
    {
        $oaHex = Base58::decode($oaAddress);
        $btcAddr = substr($oaHex->getHex(), 2, -8);
        $btcChecksum = Base58::checksum(Buffer::hex($btcAddr));
        return Base58::encode(Buffer::hex($btcAddr.$btcChecksum->getHex()));
    }
    
    public static function pubkeyHashToAssetId($pubkeyHash)
    {
        $hash160 = Hash::sha256ripe160(Buffer::hex("76". "a9". "14". $pubkeyHash."88"."ac"));
        $scriptHex = dechex(self::getOaVersionByte()) . $hash160->getHex(); 
        $checksum = Base58::checksum(Buffer::hex($scriptHex))->getHex();
        return Base58::encode(Buffer::hex($scriptHex.$checksum)); # add checksum & encode
    }

    public static function generateAssetId($pubkey)
    {
        $pubkeyHash = Hash::sha256ripe160(Buffer::hex($pubkey))->getHex();
        return self::pubkeyHashToAssetId($pubkeyHash);
    }
    

    public static function encodeLeb128($x)
    {
        if ($x < 0) {
            throw new InvalidArgumentException("Value can't be < 0. Use sencode().", 10);
        }
        $str = '';
        do {
            $char = $x & 0x7f;
            $x >>= 7;
            if($x > 0){
                $char |= 0x80;
            }
            $str .= chr($char);
        } while ($x);
        
        return $str;
    }

    public static function decodeLeb128($str, &$x, $maxlen = 16)
    {
        $len = 0;
        $x = 0;
        while($str){
            $char = substr($str, 0, 1);
            $char = ord($char);
            $str = substr($str, 1);
            
            $x |= ($char & 0x7f) << (7 * $len);
            $len++;
            
            #Bin::debugInt($char);
            
            if(($char & 0x80) == 0){
                break;
            }
            
            if($len >= $maxlen){
                throw new RuntimeException('Max length '.$maxlen.' reached.', 20);
            }
        }
        return $len;
    }

    public function validAssetId($assetId)
    {
        if (is_null($assetId) || strlen($assetId) != 34) {
            return false;
        }

        $decoded = Base58::decode($assetId);
        if (dechex(substr($decoded,0,2)) != self::getOaVersionByte()) {
            return false;
        }
        //ToDo::
    }

    public function oaAddressToAssetId($oaAddress)
    {

    }

    public static function readVarInteger($data, $offset = 0)
    {
        if (is_null($data)) {
            throw new InvalidArgumentException('Value can not be < NULL.', 10);
        }
        $buffer = Buffer::hex($data);
        if ($buffer->getSize() < 1 + $offset) {
            return [null, $offset + 1];
        }
        if ($firstByte < 0xfd) {
            return [$firstByte, $offset + 1];
        } elseif ($firstByte == 0xfd) {
            return [substr($byte, 1, 2), $offset + 3];
        } elseif ($firstByte == 0xfe) {
            return [substr($byte, 1, 4), $offset + 5];
        } elseif ($firstByte == 0xff) {
            return [substr($byte, 1, 4), $offset + 9];
        } else {
            throw new InvalidArgumentException('Var Integer MaxSize Exceeded', 10);
        }
    }

    public function readLeb128()
    {
        //ToDo::
    }

    public function calcVarIntegerVal()
    {
    }
}
