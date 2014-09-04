<?php

namespace Core\View;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Template engine
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @package core
 * @subpackage view
 * @version $Id$
 */
abstract class View
{
  protected $event_dispatcher;
  
  /**
   * Output buffer
   *
   * @var unknown_type
   */
  protected $buffer;

  protected $options;

  protected $template;

  protected $logger;

  protected $decorator;
  
  private $isDecorator;
  
  private $id;

  /**
   *
   * @var CacheView
   */
  protected $cache;

  /**
   * Contiene le variabili di template
   *
   * @var array
   */
  public $variables = array();

  /**
   * Suffisso di ogni template
   *
   * @var string
   */
  protected $suffix;

  /**
   *
   * @var mixed
   */
  protected $context;

  public function __construct($context, $options, EventDispatcher $eventDispatcher = null)
  {
    $this->context = $context;
    
    if(!$eventDispatcher)
    {
      // prova prima con context
      if(is_callable(array($this->context, 'getEventDispatcher')))
      {
        $this->event_dispatcher = $this->context->getEventDispatcher();
      }
      else
      {
        throw new \Exception('Need a EventDispatcher instance available in the context'); // problemi di compatibilità
      }
    }

    $this->options = array_merge(array('default_path' => '',
    																	 'template_extension' => '',
                                       'cache_driver' => 'sfNoCache'
                                  ),
                                 $options);
    
    if(isset($options['id']))
    {
      $this->setId($options['id']);
    }

    $this->event_dispatcher = $eventDispatcher;
  }
  
  public function __destruct()
  {
    unset($this->buffer, $this->variables);
  }

  public function __clone()
  {
    $this->decorator = null;
    $this->id = null;
    
    $this->cache = null;
    //$this->initializeCache();
  }

  public function render($variables = array())
  {
    $this->event_dispatcher->dispatch('view.pre_render', new GenericEvent($this));
    
    $this->preRender();

    $this->addVariables($variables);
    
    $this->variables = (array) $this->event_dispatcher->dispatch('view.filter_parameters', new GenericEvent($this, $this->variables))->getArguments();

    $this->buffer = $this->doRender();

    if($this->decorator)
    {
      //Logger::log(sprintf('View | render | loading decorator "%s"', $this->decorator->getTemplate()), 6);

      $this->buffer = $this->decorator->render();
    }
    
    $this->event_dispatcher->dispatch('view.post_render', new GenericEvent($this));

    $this->postRender();

    return $this->buffer;
  }
  
  public function setId($id)
  {
    $this->id = $id;
  }
  
  public function getId()
  {
    return $this->id = ($this->id ? $this->id : $this->generateId());
  }
  
  public function generateId()
  {
    if(!isset($this->template))
    {
      throw new \Exception('Cannot create id view without template name initialized');
    }
    
    $id = md5($this->template);    
    $id = $this->event_dispatcher->dispatch('view.filter_id', new GenericEvent($this, $id))->getArguments();
    
    return $id;
  }

  abstract public function doRender();

  /**
   *
   */
  public function postRender()
  {

  }

  /**
   *
   */
  public function preRender()
  {

  }

  public function setLogger($logger)
  {
    $this->logger = $logger;
  }

  public function setSuffix($suffix)
  {
    $this->suffix = $suffix;
  }

  public function getSuffix()
  {
    return $this->suffix;
  }

  /**
   * Ritorna
   *
   * @return string
   */
  public function getTemplateExtension()
  {
    return $this->options['template_extension'];
  }

  /**
   * Imposta un template per il renderer
   * 
   * Il template viene cercato nella lista ordinata dei path dei template correntemente inizializzati
   *
   * Note: I path assoluti sono comunque relativi ai path registrati
   *
   * @param string $template
   */
  public function setTemplate($template)
  {
    foreach ((array) $this->getDefaultPath() as $path )
    {
      $this->template = str_replace('//', '/', $path . DIRECTORY_SEPARATOR . $template . $this->getSuffix() . $this->getTemplateExtension());
      if(file_exists($this->template))
      {
        return;
      }
    }
    throw new \Exception(sprintf('View "%s" non trovata', $this->template));
  }

  public function getDefaultPath()
  {
    return $this->options['default_path'];
  }

  /**
   * ritorna il path del template
   *
   * @return string
   */
  public final function getTemplate()
  {
    return $this->template;
  }

  public final function getBuffer()
  {
    return $this->buffer;
  }

  /**
   * Aggiunge una variabile di template
   *
   * @param string $key
   * @param mixed $value Pointer to the variable
   */
  public function addVariable($key, & $value)
  {
    $this->variables[$key] = & $value;
  }

  public function & getVariable($key)
  {
    return $this->variables[$key];
  }

  public function addVariables($variables)
  {
    $this->variables = array_merge($this->variables, $variables);
  }

  /**
   * 
   * @param string $template
   * @return View
   */
  public function decorate($template = null)
  {
    if(is_string($template))
    {
      $this->decorator = clone $this;
      $this->decorator->isDecorator(true);

      $this->decorator->variables = & $this->variables; // passa al decorator le variabili del template da decorare (per retrocompatibilità)

      $this->decorator->setTemplate($template);
      $this->decorator->addVariable('_content', $this->buffer);
    }
    else
    {
      $this->decorator = null;
    }
    
    return $this;
  }
  
  public function isDecorator($value = null)
  {
    if($value !== null)
    {
      return $this->isDecorator = $value;
    }
    $this->isDecorator = $value;
  }

  /**
   * Ritorna una stringa alfabetica indicante il nome del motore
   *
   * @return string
   */
  abstract public function getEngineName();

  /**
   * @return mixed
   */
  public final function getContext()
  {
    return $this->context;
  }
  
  public function setEventDispatcher($event_dispatcher)
  {
    $this->event_dispatcher = $event_dispatcher;
  }
  
  /**
   * @return CacheView
   */
  public function getCache()
  {
    return $this->cache;
  }

  public function initializeCache($params = array())
  {
    $options = array(
      'driver'  => $this->options['cache_driver'],
      'params' => $params
    );
    
    if($this->event_dispatcher)
    {
      $options = $this->event_dispatcher->filter(new sfEvent($this, 'view.cache_configuration'), $options)->getReturnValue();
    }
    
    $this->cache = new CacheView($options);
  }
  
  public function esc_entities($value)
  {
    // Numbers and boolean values get turned into strings which can cause problems
    // with type comparisons (e.g. === or is_int() etc).
    return is_string($value) ? htmlentities($value, ENT_QUOTES, Config::get('charset', 'UTF-8')) : $value;
  }

}
