<?php

namespace Core\Plugin\CMS;

use Core\Plugin\PluginInterface;
use Core\Routing\Route;

class Plugin implements PluginInterface
{
  const NAME = 'Cms';
  
  public function initialize(Core $context)
  {
      /**
       *  @todo no need anymore for this with namespace, use autoload...
       *

      $context
      ->getConfiguration()
      ->setApplicationPath(
        array_merge(
          (array) $context->getConfiguration()->getApplicationPath(ApplicationConfiguration::PATH_CONTROLLER),
          array(Config::get('MAIN/core_path') . '/lib/plugins/cms/controller')),
        ApplicationConfiguration::PATH_CONTROLLER
    );

    $context
      ->getConfiguration()
      ->setApplicationPath(
        array_merge(
          (array) $context->getConfiguration()->getApplicationPath(ApplicationConfiguration::PATH_VIEW),
          array(Config::get('MAIN/core_path') . '/lib/plugins/cms/view')),
        ApplicationConfiguration::PATH_VIEW
    );
       *
       * */


      $context->getRouting()->prependRoute(
          new Route(
              'cms_admin',
              array(
                  'url' => '/cms-admin/:module/:p/*',
                  'params'  => array('controller' => 'Admin', 'action' => 'index')
              )
          )
      );
      $context->getRouting()->prependRoute(
          new Route(
              'cms',
              array(
                  'url' => '/:slug',
                  'class'  => 'CmsRoute',
                  'params'  => array('controller' => 'Cms', 'action' => 'show'))));
  }
  
  public function install()
  {
    // crea il modello
    
    // crea la tabella nel database
    
    // sposta gli assets
  }
}
