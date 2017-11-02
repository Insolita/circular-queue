<?php
/**
 * Created by solly [30.10.17 22:37]
 */

namespace insolita\cqueue\Converters;

use insolita\cqueue\Contracts\PayloadConverterInterface;

class AsIsConverter implements PayloadConverterInterface
{
    public function toIdentity($payload)
    {
        return $payload;
    }
    
    public function toPayload($identity)
    {
        return $identity;
    }
}

