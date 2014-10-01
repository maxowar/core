<?php

namespace Core\Http\Event;

use Core\Http\Request;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class FilterRequest
 * @package Core\Http\Event
 */
class FilterRequest extends Event
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }
} 