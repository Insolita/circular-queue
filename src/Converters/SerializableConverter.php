<?php
/**
 * Created by solly [30.10.17 22:37]
 */

namespace insolita\cqueue\Converters;

use insolita\cqueue\Contracts\PayloadConverterInterface;
use function serialize;
use function unserialize;

class SerializableConverter implements PayloadConverterInterface
{
    public function toIdentity($payload)
    {
        return serialize($payload);
    }
    
    public function toPayload($identity)
    {
        return unserialize($identity);
    }
}

