<?php

namespace Core\Util\Parameter;

/**
 * A parameter holder with immutable values and keys
 *
 * @package Core\Util\Parameter
 */
class Immutable extends Holder
{
    public function set($key, $val)
    {
        throw new \LogicException('Cannot change immutable values');
    }
} 