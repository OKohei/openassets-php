<?php

namespace OKohei\OpenAssets\Transactions;

use BitWasp\Buffertools\Buffer;
use Exception;

class OutPoint
{
    public $hash;
    public $index;
    
    public function __construct($hash, $index)
    {
        $buffer = Buffer::hex($hash);
        if ($buffer->getSize() != 32) {
            throw new Exception('hash must be exactly 32 bytes.');
        }

        if ($index < 0 || $index > 4294967295) {
            throw new Exception('index must be in range 0x0 to 0xffffffff.');
        }
        $this->hash = $hash;
        $this->index = $index;
    }
}
