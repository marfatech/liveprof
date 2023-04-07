<?php

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace MarfaTech\LiveProfiler;

interface DataPackerInterface
{
    /**
     * @param array $data
     * @return string
     */
    public function pack(array $data);

    /**
     * @param string $data
     * @return array
     */
    public function unpack($data);
}
