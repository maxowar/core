<?php

namespace Core\Http\Event;

use Core\Http\Response;
use Symfony\Component\EventDispatcher\Event;

class FilterResponse extends Event
{
    private $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return \Core\Http\Response;
     */
    public function getResponse()
    {
        return $this->response;
    }
}