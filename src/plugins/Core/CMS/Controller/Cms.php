<?php

namespace Core\CMS\Controller;

use Core\Controller\Controller;
use Core\Http\Request;
use Core\Http\Response;

class Cms extends Controller
{
  public function show(Request $request, Response $response)
  {
    $page = $this->context->getRouting()->getMatchedRoute()->getPage();
    
    // redirect?
    if($page->getRedirect())
    {
        $response->redirect($page->getRedirect());
    }
    
    // headers
    $this->getFrontController()->addMeta('title', $page->getTitle());
    $this->getFrontController()->addMeta('description', $page->getDescription());
    $this->getFrontController()->addMeta('keywords', $page->getKeywords());
    
    // set rendering
    if($page->getTemplate())
    {
      $this->getView()->setTemplate($this->getModuleName() . '/' . $page->getTemplate());
    }
    else
    {
      $this->getView()->setTemplate($this->getModuleName() . '/' . 'Show');
    }
    
    if($page->getDecorator())
    {
      $this->getView()->decorate($this->getModuleName() . '/' . $page->getDecorator());
    }
  }
}
