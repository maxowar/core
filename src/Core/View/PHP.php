<?php

namespace Core\View;

/**
 * Motore di rendering PHP
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @package core
 * @subpackage view
 * @version $Id$
 */
class PHP extends View
{
  private $appendToSlot;
  
  /**
   * Contiene la lista di variabili "slot"
   *
   * @var array Array di stringhe
   */
  private $slots = array();

  public function __construct($context, $options, $eventDispatcher = null)
  {
    parent::__construct($context, $options, $eventDispatcher);

    if(!isset($options['template_extension']))
    {
      $this->options['template_extension'] = '.phtml';
    }
  }

  public function getEngineName()
  {
    return 'PHP';
  }

  /**
   * Gestisce tutte le chiamate PHP del tipo:
   *
   * <code>$this->method()</code>
   *
   * dal template come chiamate all'oggetto Core
   *
   * @todo implementare un sistema di callback per gestire dinamicamente questa parte di codice
   *
   * @param string $method
   * @param array $parameters
   */
  public function __call($method, $parameters)
  {
    if(is_callable(array($this->context, $method)))
    {
      return call_user_func_array(array($this->context, $method), $parameters);
    }

    throw new RuntimeException("Metodo PHPView::$method() sconosciuto");
  }

  public function __get($key)
  {
    return $this->context->$key;
  }

  public function doRender()
  {
    //Logger::log(sprintf('PHPView | doRender | rendering template "%s"', $this->getTemplate()), 6);

    ob_start();

    extract($this->variables, EXTR_OVERWRITE);

    require $this->getTemplate();

    $content = ob_get_contents();

    ob_end_clean();

    return $content;
  }

  /**
   * Stampa nello stdout un template renderizzato
   *
   * @param string $template   Il nome del template grafico da caricare
   * @param array  $parameters Contiene le variabili da passare al template
   */
  public function loadTemplate($template, $parameters = array(), $configuration = null)
  {
    echo $this->renderTemplate($template, $parameters, $configuration);
  }

  /**
   * Renderizza un template grafico
   * 
   * Scorciatoia per creare un oggetto View inizializzato con valori dell'istanza View che lo crea 
   *
   * I path dei template quando non sono assoluti sono realtivi all'applicazione, di conseguenza
   * i path devono esplicitamente contenere la directory del modulo
   *
   * ATTENZIONE che il path definito in <var>$template</var> è case sensitive
   *
   * Esempio di passaggio dei parametri:
   * <code>
   * // in un template
   * $var1 = 'Ciao';
   * $var2 = 'Massimo';
   * $this->loadTemplate('template/name', array('nome_var1' => $var1, 'nome_var2' => $var2));
   *
   * // nel template "template/name"
   * echo $nome_var1 . ' ' . $nome_var2;
   * </code>
   *
   * Siete veramente raccomandati ad usare questa funzione anzichè includere manualmente i file dei template
   *
   * @param string $template   Il nome del template grafico da caricare
   * @param array  $parameters Contiene le variabili da passare al template
   *
   */
  public function renderTemplate($template, $parameters = array(), $configuration = null)
  {
    $renderer = clone $this;

    if($configuration instanceof Closure)
    {
      $configuration($renderer);
    }

    $renderer->setTemplate($template);
    $renderer->addVariables($parameters);

    return $renderer->render();
  }

  public function getContent()
  {
    if(isset($this->variables['_content']))
    {
      return $this->variables['_content'];
    }
  }

  /**
   * Inizializza uno slot di nome <var>$name</var>
   *
   * Uno slot è un meccanismo per passare parti di HTML renderizzato all'eventuale template decoratore
   *
   * @param string $name
   */
  public function slot($name, $append = false)
  {
    ob_start();
    
    $this->appendToSlot = $append;
    
    array_push($this->slots, $name);
  }

  /**
   * Termina l'inizializzazione di uno slot
   *
   * @param string $name Nome dello slot da chiudere. Mantenuta per retrocompatibilità
   * @throws ViewException
   */
  public function endSlot($name = null)
  {
    $lastSlot = array_pop($this->slots);

    if(isset($name) && $lastSlot != $name)
    {
      throw new \Exception(sprintf('Ending rendering slot non started. End %s before %s', $lastSlot, $name));
    }

    $content = ob_get_contents();

    if($this->decorator)
    {
      if($this->appendToSlot && $var = $this->decorator->getVariable($lastSlot))
      {
        $content = $var . $content;
        $this->appendToSlot = false;
      }

      $this->decorator->addVariable($lastSlot, $content);
    }

    ob_end_clean();

    return $content;
  }

  
}
