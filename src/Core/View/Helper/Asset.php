<?php

namespace Core\View\Helper;

use Core\Util\Utility;

/**
 * Class Asset
 *
 * @todo sposare i metodi di rendering nell'Helper della View
 *
 * @package Core\View\Asset
 */
class Asset
{
    protected $filename;

    protected $stylesheets;

    protected $javascripts;

    protected $options;

    public function __construct()
    {

        $this->stylesheets = array();
        $this->javascripts = array();
    }

    /**
     * Aggiunge un javascript nel tag HEAD
     *
     * La lista dei files è inidicizzata con chiave inizializzata con il nome del file del foglio stesso
     *
     * Note:
     * - Il metodo si occupa di aggiungere il suffisso <em>.js</em> ai nomi dei files
     * - Il path di <var>$filename</var> pu&ograve; essere assoluto
     *
     * @param string $filename Path del file relativo all'applicazione
     * @param string $position Valori validi [after|before]
     */
    public function addJavascript($filename, $position = 'after')
    {/*
        if(!Utility::isValidUri($filename))
        {
            if(!Utility::isAbsolutePath($filename))
            {
                $filename = strtolower('/' . $this->getConfiguration()->getApplicationName() . '/js/' . $filename);
            }
        }*/

        if($position == 'before')
        {
            $this->javascripts = array_merge(array($filename => $filename), $this->javascripts);
            return;
        }
        $this->javascripts[$filename] = $filename;
        return;
    }

    /**
     * Aggiunge una lista di files javascript
     *
     * @param array $filenames
     */
    public function addJavascripts($filenames)
    {
        foreach($filenames as $filename)
        {
            $this->addJavascript($filename);
        }
    }

    /**
     * Aggiunge un foglio di stile nel tag HEAD
     *
     * La lista dei files è inidicizzata con chiave inizializzata con il nome del file del foglio stesso
     *
     * Note:
     * - Il metodo si occupa di aggiungere il suffisso <em>.css</em> ai nomi dei files
     * - Il path di <var>$filename</var> pu&ograve; essere assoluto
     *
     * Esempio d'uso:
     * <code>
     * Core::addStylesheet('main');
     * Core::addStylesheet('ie6', array('position' => 'before'));
     * Core::addStylesheet('print', array('media' => 'print'));
     *
     * // genera
     *
     * <link href="/application_name/css/ie6.css" type="text/css" rel="stylesheet" media="all" />
     * <link href="/application_name/css/main.css" type="text/css" rel="stylesheet" media="all" />
     * <link href="/application_name/css/print.css" type="text/css" rel="stylesheet" media="print" />
     *
     * </code>
     *
     * @param string $filename   Path del file relativo all'applicazione
     * @param array  $parameters
     */
    public function addStylesheet($filename, $parameters = array())
    {/*
        if(!Utility::isAbsolutePath($filename) && !Utility::isValidUri($filename))
        {
            $filename = strtolower('/' . $this->getConfiguration()->getApplicationName() . '/css/' . $filename);
        }
*/
        $position = isset($parameters['position']) ? $parameters['position'] : 'after';

        $ary = array('filename' => $filename,
            'media'    => isset($parameters['media']) ? $parameters['media'] : 'all',
            'version'  => isset($parameters['version']) ? $parameters['version'] : null);

        if($position == 'before')
        {
            $this->stylesheets = array_merge(array($filename => $ary), $this->stylesheet);
            return;
        }
        else
        {
            $this->stylesheets[$filename] = $ary;
            return;
        }
    }

    /**
     * Aggiunge una lista di files javascript
     *
     * @param array $filenames
     */
    public function addStylesheets($filenames)
    {
        foreach($filenames as $filename => $parameters)
        {
            $this->addStylesheet($filename, $parameters);
        }
    }

    /**
     * Rimuove tutti i javascript
     */
    public function cleanJavascript()
    {
        $this->javascripts = array();
    }

    /**
     * Rimuove tutti i fogli di stile
     */
    public function cleanStylesheet()
    {
        $this->stylesheet = array();
    }

    /**
     * Ritorna la stringa da inserire nell'header html per caricare i javascript
     *
     * @return string
     */
    public function loadJavascript()
    {
        $str = '';
        foreach ($this->javascripts as $filename => $javascript)
        {
            $str .= sprintf('  <script src="%s%s"></script>' . "\n", $javascript , (Utility::isValidUri($javascript)? '':'.js' ) );
        }
        return $str;
    }

    /**
     * Ritorna la stringa da inserire nell'header html per caricare i fogli di stile
     *
     * @return string
     */
    public function loadStylesheet()
    {
        $str = '';
        foreach ($this->stylesheets as $filename => $stylesheet)
        {
            $str .= sprintf('  <link href="%s.css%s" type="text/css" rel="stylesheet" media="%s" >' . "\n",
                $stylesheet['filename'],
                isset($stylesheet['version']) ? '?' . $stylesheet['version'] : '',
                $stylesheet['media']);
        }
        return $str;
    }
}