<?php

namespace Core\Filter;

use Symfony\Component\EventDispatcher\Event;

class FilterEvent extends Event
{
    protected $filterManager;

    public function __construct(Manager $filterManager)
    {
        $this->filterManager = $filterManager;
    }

    public function getFilterManager()
    {
        return $this->filterManager;
    }
}