<?php

namespace Core\Http;
use Core\Util\Parameter\Immutable;

/**
 * Base rapresentation of a HTTP request
 *
 * @package Core\Http
 */
class Request
{
    const METHOD_HEAD    = 'HEAD';
    const METHOD_GET     = 'GET';
    const METHOD_POST    = 'POST';
    const METHOD_PUT     = 'PUT';
    const METHOD_PATCH   = 'PATCH';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_PURGE   = 'PURGE';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_TRACE   = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';


    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $query;

    /**
     * @var array
     */
    protected $files;

    /**
     * @var array
     */
    protected $cookies;

    /**
     * @var array
     */
    protected $server;

    /**
     * build a Request instance
     *
     * @param null $query
     * @param null $data
     * @param null $files
     * @param null $cookies
     * @param null $server
     */
    public function __construct($query = null, $data = null, $files = null, $cookies = null, $server = null)
    {
        $this->initialize($query, $data, $files, $cookies, $server);
    }

    /**
     * Abstract constructor
     *
     */
    public function initialize($query = array(), $data = array(), $files = array(), $cookies = array(), $server = array())
    {
        $this->query   = new Immutable($query);
        $this->data    = $data;
        $this->files   = $files;
        $this->cookies = $cookies;
        $this->server  = $server;
    }

    /**
     * Instantiate a Request from PHP globals arrays
     *
     * @return Request
     */
    public static function createFromPHP()
    {
        return new self($_GET, $_POST, self::convertFileInformation($_FILES), $_COOKIE, $_SERVER);
    }

    /**
     * return POST data
     *
     * @param null $key
     * @return mixed
     */
    public function getData($key = null)
    {
        if($key)
        {
            return $this->data[$key];
        }
        return $this->data;
    }

    /**
     * return GET data
     *
     * @return mixed
     */
    public function getQuery($key = null, $default = null)
    {
        if($key)
        {
            return isset($this->query[$key]) ? $this->query[$key] : $default;
        }
        return $this->query;
    }

    /**
     * return the hostname or servername
     *
     * @return mixed
     */
    public function getHost()
    {
        return $this->server['HTTP_HOST'] ? $this->server['HTTP_HOST'] : $this->server['SERVER_NAME'];
    }

    /**
     * return the Uri
     *
     * @return mixed
     */
    public function getUri()
    {
        return $_SERVER['REQUEST_URI'];
    }

    public function isPost()
    {
        return strtoupper($this->getMethod()) == self::METHOD_POST;
    }

    public function isGet()
    {
        return strtoupper($this->getMethod()) == self::METHOD_GET;
    }

    public function isPut()
    {
        return strtoupper($this->getMethod()) == self::METHOD_PUT;
    }

    public function isDelete()
    {
        return strtoupper($this->getMethod()) == self::METHOD_DELETE;
    }

    /**
     * Ritorna il metodo HTTP della request
     *
     * Viene data precedenza al metodo della richiesta fittizio, impostabili attraverso il parametro 'request_method',
     * questo per simulare tutti i metodi disponibili dal protocollo HTTP ma non usabili
     * attraverso i browser
     *
     * Esempio di utilizzo:
     * <code>
     * if(Routing::getRequestMethod() == Routing::POST)
     * {
     *   echo "POST";
     * }
     * </code>
     *
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html HTTP Method Definitions
     *
     * @return string Il metodo HTTP della request
     */
    public function getMethod()
    {
        return $this->server['REQUEST_METHOD'];
    }

    /**
     * controlla che sia stata effettuata una richiesta asincrona con l'oggetto
     * javascript XMLHTTPRequest
     *
     * @return boolean
     */
    public function isXHR()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
    }

    /**
     * Retrieves an array of files.
     *
     * {@see sfWebRequest} property of symfony
     *
     * @param  string $key  A key
     * @return array  An associative array of files
     */
    public function getFiles($key = null)
    {
        return null === $key ? $this->fixedFileArray : (isset($this->fixedFileArray[$key]) ? $this->fixedFileArray[$key] : array());
    }

    /**
     * Converts uploaded file array to a format following the $_GET and $POST naming convention.
     *
     * It's safe to pass an already converted array, in which case this method just returns the original array unmodified.
     *
     * {@see sfWebRequest} property of symfony
     *
     * @param  array $taintedFiles An array representing uploaded file information
     *
     * @return array An array of re-ordered uploaded file information
     */
    protected static function convertFileInformation(array $taintedFiles)
    {
        $files = array();
        foreach ($taintedFiles as $key => $data)
        {
            $files[$key] = self::fixPhpFilesArray($data);
        }

        return $files;
    }

    /**
     * {@see sfWebRequest} property of symfony
     *
     * @param unknown_type $data
     */
    protected static function fixPhpFilesArray($data)
    {
        $fileKeys = array('error', 'name', 'size', 'tmp_name', 'type');
        $keys = array_keys($data);
        sort($keys);

        if ($fileKeys != $keys || !isset($data['name']) || !is_array($data['name']))
        {
            return $data;
        }

        $files = $data;
        foreach ($fileKeys as $k)
        {
            unset($files[$k]);
        }
        foreach (array_keys($data['name']) as $key)
        {
            $files[$key] = self::fixPhpFilesArray(array(
                'error'    => $data['error'][$key],
                'name'     => $data['name'][$key],
                'type'     => $data['type'][$key],
                'tmp_name' => $data['tmp_name'][$key],
                'size'     => $data['size'][$key],
            ));
        }

        return $files;
    }
}