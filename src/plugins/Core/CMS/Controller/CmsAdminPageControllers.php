<?php

class CmsAdminPageControllers extends Controllers
{
  public function executeIndex()
  {
    $this->addMeta('title', 'Elenco pagine');
    
    $this->addVar('pages', CmsPageTable::getInstance()->findAll());
  }
}