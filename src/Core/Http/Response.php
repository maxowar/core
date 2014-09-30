<?php

namespace Core\Http;

/**
 * Generic rapresentation of a HTTP Response
 *
 * Use this class to send data to the client/browser
 *
 * @package Core\Http
 */
class Response
{

    protected $headers;

    /**
     * Mappa dei codici di stato HTTP
     *
     * @var array
     */
    static protected $statusTexts = array(
        '100' => 'Continue',
        '101' => 'Switching Protocols',
        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        '306' => '(Unused)',
        '307' => 'Temporary Redirect',
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '407' => 'Proxy Authentication Required',
        '408' => 'Request Timeout',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length Required',
        '412' => 'Precondition Failed',
        '413' => 'Request Entity Too Large',
        '414' => 'Request-URI Too Long',
        '415' => 'Unsupported Media Type',
        '416' => 'Requested Range Not Satisfiable',
        '417' => 'Expectation Failed',
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Timeout',
        '505' => 'HTTP Version Not Supported',
    );

    const MIME_HTML = 'text/html';
    const MIME_JSON = 'application/json';


    protected $content;

    public function __construct()
    {
        $this->headers = array();
    }

    /**
     * Effettua un redirect ad un URI
     *
     * @param mixed   $route
     * @param integer $cod   Un codice HTTP valido
     */
    public function redirect($uri = '' , $cod = 302)
    {
        $uri = $this->normalizeUrl($uri);

        if(Config::get('application.debug'))
        {
            //$trace = debug_backtrace();
            //Logger::log('Routing | redirect | Redirect invocato in "' . $trace[0]['file'] . '(' . $trace[0]['line'] . ')" codice HTTP: ' . $cod, Logger::DEBUG);
        }

        // url
        if((Utility::isValidUri($uri) || Utility::isAbsolutePath($uri)))
        {
            $this->doRedirect($uri);
        }
    }

    /**
     * Inizializza gli header HTTP per effettuare un redirect
     *
     * @param string  $destination L'URI di destinazione
     * @param integer $cod
     */
    private final function doRedirect($destination, $cod = 302)
    {
        if(array_key_exists($cod, self::$statusTexts))
        {
            $textStatus = self::$statusTexts[$cod];
        }
        else
        {
            $cod = 302;
            $textStatus = self::$statusTexts[$cod];
        }

        //Logger::info('Routing | redirect | Redirecting all\'indirizzo "' . $destination . '"');

        header('HTTP/1.1 '.$cod.' '.$textStatus);
        header('Location: ' . $destination);
        exit(0);
    }

    /**
     * Normalizzazione dell'url
     *
     * - url inizia con '/'
     * - query_string eliminata
     * - caratteri '/' consecutivi eliminati
     *
     * @param $url
     * @return unknown_type
     */
    public static function normalizeUrl(&$url)
    {
        // an URL should start with a '/', mod_rewrite doesn't respect that, but no-mod_rewrite version does.
        if ('/' != substr($url, 0, 1))
        {
            $url = '/'.$url;
        }

        // we remove the query string
//    if (false !== $pos = strpos($url, '?'))
//    {
//      $url = substr($url, 0, $pos);
//    }

        // remove multiple /
        $url = preg_replace('#/+#', '/', $url);

        return $url;
    }

    public function setContentType($mimetype)
    {
        $this->setHeader('Content-Type', $mimetype);

        return $this;
    }

    public function setHeader($header, $value)
    {
        $this->headers[$header] = $value;

        return $this;
    }

    public function sendHeaders()
    {
        foreach($this->headers as $name => $value)
        {
            header(sprintf(strtolower($name) . ': ' . $value), true);
        }

    }

    public function setJsonContent($data, $options = 0)
    {
        $this->content = json_encode($data, $options);
    }

    public function headersSent()
    {
        return headers_sent();
    }

    public function send()
    {
        $this->sendHeaders();
        print $this->content;
    }

    public function setFile($filename)
    {
        $this->setHeader('Content-Length', filesize($filename));
        $this->setHeader('Content-Type', '');

        $this->data = file_get_contents($filename);

        return $this;
    }

    /**
     * Set the content of the HTTP response
     *
     * @param string $content
     * @param string $mimetype
     * @return $this
     */
    public function setContent($content, $mimetype = 'text/hmtl')
    {
        $this->content = $content;
        $this->setContentType($mimetype);

        return $this;
    }
}