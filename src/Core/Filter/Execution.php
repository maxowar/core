<?php

namespace Core\Filter;

use Core\Http\Event\FilterResponse;
use Core\Http\Response;

/**
 * Execution filter esegue la logica di back-end dell'applicazione (controller execution)
 *
 * @author Massimo Naccari <massimonaccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage filter
 */
class Execution extends Filter
{
    public function execute($coin = null)
    {
        $context = $this->getContext();

        //Logger::info(sprintf('ExecutionFilter | execute | Execute controller "%s/%s"', $dispatcher->getModuleName(), $dispatcher->getActionName()));

        // esecuzione della logica business del controller
        $response = $context->handle();

        $this->keepon();

        $response = $context->getEventDispatcher()->dispatch('response.filter', new FilterResponse($context->getInstance()->getResponse(), array('output' => $response)))->getResponse();

        if (!($response instanceof Response))
        {
            throw new \InvalidArgumentException('Controller must return a Response instance type');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $response->send();

        return;
    }


}
