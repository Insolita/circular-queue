<?php
/**
 * Created by solly [30.10.17 15:56]
 */

namespace insolita\cqueue\Contracts;


interface PayloadConverterInterface
{
    /**
     * Extract identity from payload for usage in queue
     * @param $payload
     *
     * @return int|string
     */
    public function toIdentity($payload);
    
    /**
     * Resolve and return payload by identity from queue
     * @param int|string $identity
     *
     * @return mixed
     */
    public function toPayload($identity);
}
