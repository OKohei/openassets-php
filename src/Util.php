<?php

namespace OKohei\OpenAssets;

use TheFox\Utilities\Leb128;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Base58;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkey;
use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Script\ScriptInfo\Multisig;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkeyHash;
use BitWasp\Bitcoin\Script\Factory\P2shScriptFactory;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;

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

    public static function oaAddressToBtcAddress($oaAddress)
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

    public static function generateAssetId(PublicKey $pubkey)
    {
        $pubkeyHash = Hash::sha256ripe160($pubkey->getBuffer())->getHex();
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
        
        return unpack('H*', $str);
    }

    public static function decodeLeb128($leb128)
    {
        $base = null;
        $bytes = str_split($leb128, 2);
        $numItems = count($bytes);
        $i = 0;
        foreach($bytes as $byte) {
            if (++$i !== $numItems) {
                $base .= Buffer::hex($byte)->getInt() >= 128 ? $byte : $byte.'|';
            } else {
                $base .= $byte;
            }
        };
        $data = explode('|', $base);
        foreach ($data as $str) {
            $len = Leb128::udecode(pack('H*', $str), $x);
            $res[] = $x;
        }
        return $res;
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

    public static function oaAddressToAssetId($oaAddress)
    {
        $btcAddress = self::oaAddressToBtcAddress($oaAddress);
        return self::btcAddressToAssetId($btcAddress);
    }
    
    public static function btcAddressToAssetId($btcAddress)
    {
        $pubkeyHash = AddressFactory::fromString($btcAddress)->getHash();
        return self::pubkeyHashToAssetId($pubkeyHash->getHex());
    }
    
    public static function readVarInteger($data, $offset = 0)
    {
        if (is_null($data)) {
            throw new InvalidArgumentException('Value can not be < NULL.', 10);
        }
        $buffer = Buffer::hex(substr($data,$offset * 2));
        if ($buffer->getSize() < 1 + $offset) {
            return [null, $offset + 1];
        }

        $firstByte = $buffer->slice(0,1)->getBinary();
        if ($firstByte < 0xfd) {
            return [$buffer->slice(0,1)->getInt(), $offset + 1];
        } elseif ($firstByte == 0xfd) {
            return [$buffer->slice(1,4)->getInt(), $offset + 3];
        } elseif ($firstByte == 0xfe) {
            return [$buffer->slice(1,8)->getInt(), $offset + 5];
        } elseif ($firstByte == 0xff) {
            return [$buffer->slice(1,16)->getInt(), $offset + 9];
        } else {
            throw new InvalidArgumentException('Var Integer MaxSize Exceeded', 10);
        }
    }

    public static function scriptToAddress($script)
    {
        $classifier = new OutputClassifier();
        $type = $classifier->classify($script);
        if ($type == OutputClassifier::MULTISIG) {
            $multiSig = new Multisig($script);
            $res = [];
            foreach($multiSig->getKeys() as $key) {
                $res[] = $key->getAddress()->getAddress();
            }
            return $res;
        } elseif ($type == OutputClassifier::PAYTOPUBKEY) {
            $pubkey = new PayToPubkey($script);
            return $pubkey[0]->getAddress();
        } elseif ($type == OutputClassifier::PAYTOSCRIPTHASH) {
            $script = AddressFactory::fromScript($script);
            return $script->getAddress();
        } elseif ($type == OutputClassifier::PAYTOPUBKEYHASH) {
            return AddressFactory::fromOutputScript($script)->getAddress();
        } 
    }

    public static function arrayFlatten(array $arr) 
    {
        $ret = array();
        foreach ($arr as $item) {
            if (is_array($item)) {
                $ret = array_merge($ret, array_flatten($item));
            } else {
                $ret[] = $item;
            }
        }

        return $ret;
    }
}
