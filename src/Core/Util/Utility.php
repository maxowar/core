<?php

function array_insert($array, $var, $position)
{
  $before = array_slice($array, 0, $position);
  $after = array_slice($array, $position);

  $return = array_merge($before, (array) $var);
  return array_merge($return, $after);
}

/**
 * Classe che raccoglie funzioni di utilità varia
 *
 *
 * @package core
 * @subpackage utility
 *
 */
class Utility
{
  private static $captcha;

  private static $csrf;

	public static function format_number($val,$format=",.2")
	{
		if (strlen($format)!=3) $format=",.2";
		$dec=substr($format,2);
		if (!is_numeric($dec)) $dec=2;
		if ($dec<0) $dec=2;
		return (number_format($val,$dec,$format{0},$format{1}));
	}

	public static function setTemplateMail($email_text,$path_template="",$template_des="",$arrData=array())
	{
		$html = $email_text;
		$text = $email_text;
		$ereg = "(\[[A-Z 0-9]+\])";

		$template_html = $path_template.$template_des.".html";
		$template_txt = $path_template.$template_des.".txt";

		if(file_exists($template_html)){
			$html = file_get_contents($template_html);
			$html= str_replace("[EMAIL_TEXT]",$email_text,$html);
			if(is_array($arrData) && count($arrData)>0)
				$html = Utility::replace($html,$arrData);
			$html= preg_replace($ereg,"",$html);
		}

		if(file_exists($template_txt)){
			$text = file_get_contents($template_txt);
			$text= str_replace("[EMAIL_TEXT]",$email_text,$text);
			if(is_array($arrData) && count($arrData)>0)
				$text = Utility::replace($text,$arrData);
			$text= preg_replace($ereg,"",$text);
		}

		$email['HTML'] = $html;
		$email['TEXT'] = $text;

		return $email;
	}

	/**
	 * Sostituisce i place holders
	 *
	 * place holders del tipo: [placeholder]
	 *
	 * @param unknown_type $email_text
	 * @param unknown_type $arrData
	 */
	public static function replace($email_text,$arrData)
	{
		if(is_array($arrData) && count($arrData)){
			foreach($arrData as $search=>$replace)
				$email_text = str_replace("[".$search."]",$replace,$email_text);
		}

		$ereg = "(\[[A-Z 0-9 _]+\])";
		$email_text = preg_replace($ereg,"",$email_text);

		return $email_text;
	}

	public static function setMail($email_text,$arrData)
	{
    return self::replace($email_text, $arrData);
	}

	public static function sendMail($fromEmail, $fromName = 'Servizio Clienti', $toEmail, $toName = '', $subject, $bodyTxt="", $bodyHtml="", $attachments = array())
	{
	  require_once dirname(__FILE__) . '/../vendor/Swift/lib/swift_required.php';
	  require_once dirname(__FILE__) . '/../vendor/Swift/lib/swift_init.php';

		if($fromEmail == "") return false;
		if($toEmail == "") return false;
		if($subject == "") return false;
		if($bodyTxt == "" && $bodyHtml == "") return false;

		// PHP Mail
		 $transport = Swift_MailTransport::newInstance();

		$mailer = Swift_Mailer::newInstance($transport);

		$message = Swift_Message::newInstance()
  		->setSubject($subject)
  		->setFrom(array('AutoSuperMarket' => 'noreply@autosupermarket.it'))
  		->setReturnPath('noreply@autosupermarket.it')
  		->setReplyTo(array($fromEmail => $fromEmail))
  		->setTo(array($toEmail => $toName))
  		->setBody($bodyTxt);
  	if($bodyHtml)
  	{
  	  $message->addPart($bodyHtml, 'text/html');
  	}
  	
  	if(Config::get('LOG/debug'))
  	{
  	  Logger::debug(sprintf('Utility | sendMail | from: %s <%s>', $fromName, $fromEmail));
  	  Logger::debug(sprintf('Utility | sendMail | to: %s <%s>', $toName, $toEmail));
  	  Logger::debug(sprintf('Utility | sendMail | subject: %s', $subject));
  	  Logger::debug(sprintf('Utility | sendMail | text/plain: %s', $bodyTxt));
  	  Logger::debug(sprintf('Utility | sendMail | text/html: %s', $bodyHtml));
  	}

		// Send the message
		return $mailer->send($message);
	}

	public static function str_encode($str) {
		$str=str_replace("/","%20",$str);
		$str=str_replace("&","^",$str);
		return $str;
	}
	public static function str_decode($str) {
		$str=str_replace("%20","/",$str);
		$str=str_replace("^","&",$str);
		return $str;
	}
	public static function bitSum2bits($sum)
	{
		$sum = (int)$sum;
  		$v = 1;
  		$arBits = array();
  		while ($v <= $sum)
  		{
    		if ($v & $sum) $arBits[] = $v;
    		$v = $v * 2;
  		}

  		return $arBits;
	}

	public static function seems_utf8($Str) {
	  $str_lenght = strlen($Str);
	 for ($i=0; $i<$str_lenght; $i++) {
	  if (ord($Str[$i]) < 0x80) continue; # 0bbbbbbb
	  elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
	  elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
	  elseif ((ord($Str[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
	  elseif ((ord($Str[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
	  elseif ((ord($Str[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
	  else return false; # Does not match any model
	  for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
	   if ((++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80))
	    return false;
	  }
	 }
	 return true;
	}

	public static function cp1252_to_utf8($str) {
        $cp1252_map = array(
		    "\xc2\x80" => "\xe2\x82\xac", /* EURO SIGN */
		    "\xc2\x82" => "\xe2\x80\x9a", /* SINGLE LOW-9 QUOTATION MARK */
		    "\xc2\x83" => "\xc6\x92",     /* LATIN SMALL LETTER F WITH HOOK */
		    "\xc2\x84" => "\xe2\x80\x9e", /* DOUBLE LOW-9 QUOTATION MARK */
		    "\xc2\x85" => "\xe2\x80\xa6", /* HORIZONTAL ELLIPSIS */
		    "\xc2\x86" => "\xe2\x80\xa0", /* DAGGER */
		    "\xc2\x87" => "\xe2\x80\xa1", /* DOUBLE DAGGER */
		    "\xc2\x88" => "\xcb\x86",     /* MODIFIER LETTER CIRCUMFLEX ACCENT */
		    "\xc2\x89" => "\xe2\x80\xb0", /* PER MILLE SIGN */
		    "\xc2\x8a" => "\xc5\xa0",     /* LATIN CAPITAL LETTER S WITH CARON */
		    "\xc2\x8b" => "\xe2\x80\xb9", /* SINGLE LEFT-POINTING ANGLE QUOTATION */
		    "\xc2\x8c" => "\xc5\x92",     /* LATIN CAPITAL LIGATURE OE */
		    "\xc2\x8e" => "\xc5\xbd",     /* LATIN CAPITAL LETTER Z WITH CARON */
		    "\xc2\x91" => "\xe2\x80\x98", /* LEFT SINGLE QUOTATION MARK */
		    "\xc2\x92" => "\xe2\x80\x99", /* RIGHT SINGLE QUOTATION MARK */
		    "\xc2\x93" => "\xe2\x80\x9c", /* LEFT DOUBLE QUOTATION MARK */
		    "\xc2\x94" => "\xe2\x80\x9d", /* RIGHT DOUBLE QUOTATION MARK */
		    "\xc2\x95" => "\xe2\x80\xa2", /* BULLET */
		    "\xc2\x96" => "\xe2\x80\x93", /* EN DASH */
		    "\xc2\x97" => "\xe2\x80\x94", /* EM DASH */

		    "\xc2\x98" => "\xcb\x9c",     /* SMALL TILDE */
		    "\xc2\x99" => "\xe2\x84\xa2", /* TRADE MARK SIGN */
		    "\xc2\x9a" => "\xc5\xa1",     /* LATIN SMALL LETTER S WITH CARON */
		    "\xc2\x9b" => "\xe2\x80\xba", /* SINGLE RIGHT-POINTING ANGLE QUOTATION*/
		    "\xc2\x9c" => "\xc5\x93",     /* LATIN SMALL LIGATURE OE */
		    "\xc2\x9e" => "\xc5\xbe",     /* LATIN SMALL LETTER Z WITH CARON */
		    "\xc2\x9f" => "\xc5\xb8"      /* LATIN CAPITAL LETTER Y WITH DIAERESIS*/
		);
        return  strtr(utf8_encode($str), $cp1252_map);
	}

	public static function qUtf8($value)
	{
		if(Utility::seems_utf8($value))
			return $value;
		return Utility::cp1252_to_utf8($value);
	}

  public static function qLatin($value)
  {
    if(Utility::seems_utf8($value))
      return utf8_decode($value);
    return $value;
  }



  // code from php at moechofe dot com (array_merge comment on php.net)
  /*
   * array arrayDeepMerge ( array array1 [, array array2 [, array ...]] )
   *
   * Like array_merge
   *
   *  arrayDeepMerge() merges the elements of one or more arrays together so
   * that the values of one are appended to the end of the previous one. It
   * returns the resulting array.
   *  If the input arrays have the same string keys, then the later value for
   * that key will overwrite the previous one. If, however, the arrays contain
   * numeric keys, the later value will not overwrite the original value, but
   * will be appended.
   *  If only one array is given and the array is numerically indexed, the keys
   * get reindexed in a continuous way.
   *
   * Different from array_merge
   *  If string keys have arrays for values, these arrays will merge recursively.
   */
  public static function arrayDeepMerge()
  {
    switch (func_num_args())
    {
      case 0:
        return false;
      case 1:
        return func_get_arg(0);
      case 2:
        $args = func_get_args();
        $args[2] = array();
        if (is_array($args[0]) && is_array($args[1]))
        {
          foreach (array_unique(array_merge(array_keys($args[0]),array_keys($args[1]))) as $key)
          {
            $isKey0 = array_key_exists($key, $args[0]);
            $isKey1 = array_key_exists($key, $args[1]);
            if ($isKey0 && $isKey1 && is_array($args[0][$key]) && is_array($args[1][$key]))
            {
              $args[2][$key] = self::arrayDeepMerge($args[0][$key], $args[1][$key]);
            }
            else if ($isKey0 && $isKey1)
            {
              $args[2][$key] = $args[1][$key];
            }
            else if (!$isKey1)
            {
              $args[2][$key] = $args[0][$key];
            }
            else if (!$isKey0)
            {
              $args[2][$key] = $args[1][$key];
            }
          }
          return $args[2];
        }
        else
        {
          return $args[1];
        }
      default :
        $args = func_get_args();
        $args[1] = self::arrayDeepMerge($args[0], $args[1]);
        array_shift($args);
        return call_user_func_array(array('Utility', 'arrayDeepMerge'), $args);
        break;
    }
  }

  public static function isAbsolutePath($path)
  {
    return preg_match('%^([a-z]:)?\\\\|^/%i', $path);
  }

  public static function isValidUri($string)
  {
    return preg_match('/\b((?#protocol)https?|ftp):\/\/((?#domain)[-A-Z0-9.]+)((?#file)\/[-A-Z0-9+&@#\/%=~_|!:,.;]*)?((?#parameters)\?[-A-Z0-9+&@#\/%=~_|!:,.;]*)?/i', $string);
  }

  public static function mkdir_r($dirname, $permessi=0755)
  {
    if(is_dir($dirname)) return true;
    return @mkdir($dirname,$permessi,true);
  }

  public static function unlink($filename)
  {
    if(!file_exists($filename)) return true;
    return @unlink($filename);
  }

  /**
   * genera un token per validare un form contro attacchi CSRF
   *
   * @return string
   */
  public static function CsrfTokenCreate()
  {
    if(self::$csrf)
    {
      return self::$csrf;
    }
    self::$csrf = $_SESSION['csrf_token_key'] = sha1(time() . 'io sono il PHP');
    $_SESSION['csrf_token_ttl'] = time() + (60 * 10); // ttl di 5 minuti

    return $_SESSION['csrf_token_key'];
  }

  /**
   *
   *
   * @param string $token
   * @return boolean
   */
  public static function CsrfTokenCheck($token)
  {
    if(!isset($_SESSION['csrf_token_key']))
    {
      return false;
    }

    if($token != $_SESSION['csrf_token_key'])
    {
      return false;
    }

    if($_SESSION['csrf_token_ttl'] < time())
    {
      return false;
    }

    return true;
  }

  public static function captchaCreate($length = 4)
  {
    if(self::$captcha)
    {
      return self::$captcha;
    }

    $alphabet = 'ABCDEFGHLMNPQRTUVZ2389';

    $captcha = '';
    for($i = 0; $i <= $length - 1; $i++)
    {
      $captcha .= $alphabet[rand(0, strlen($alphabet) - 1)];
    }

    $_SESSION['captcha'] = $captcha;

    //
    // creazione immagine
    //

    $image = imagecreatetruecolor (80, 24);

    // Make the background red
    imagefilledrectangle($image, 0, 0, 299, 99, imagecolorallocate($image, 0xFC, 0xFC, 0xFC));

    imagettftext($image,
                16,
                0,
                5,
                20,
                imagecolorallocate($image, 0x63, 0x63, 0x63),
                Config::get('MAIN/base_path') . DIRECTORY_SEPARATOR . 'doc/arial.ttf',
                $captcha);
    imagettftext($image,
                16,
                0,
                6,
                21,
                imagecolorallocate($image, 0xAA, 0xAA, 0xAA),
                Config::get('MAIN/base_path') . DIRECTORY_SEPARATOR . 'doc/arial.ttf',
                $captcha);

    imageline($image, 0, 12, 80, 12, imagecolorallocatealpha($image, 0x66, 0x66, 0x66, 100));
    imageline($image, 0, 4, 80, 4, imagecolorallocatealpha($image, 0xFF, 0x6A, 0x6A, 80));

    ob_start();
    imagepng($image);
    self::$captcha = base64_encode(ob_get_contents());
    ob_end_clean();

    return self::$captcha;
  }

  public static function captchaCheck($captcha)
  {
    return isset($_SESSION['captcha']) && strtolower($_SESSION['captcha']) == strtolower($captcha) ? true : false;
  }

  public static function dateDiff($date1, $date2)
  {
    if(is_string($date1))
    {
      $date1 = new DateTime($date1);
    }
    if(is_string($date2))
    {
      $date2 = new DateTime($date2);
    }

    return $date1->diff($date2);
  }

  public static function daysLeft(DateInterval $interval)
  {
    return $interval->format('%a');
  }

  public static function requestFiles($room)
  {
    $files = $_FILES[$room];
    $keys = array_keys($files['name']);
    $requestFiles = array();
    foreach($keys as $key)
    {
      $subkey = key($files['name'][$key]);
      $requestFiles[$key][$subkey] = array(
        'name' => $files['name'][$key][$subkey],
        'type' => $files['type'][$key][$subkey],
        'tmp_name' => $files['tmp_name'][$key][$subkey],
        'error' => $files['error'][$key][$subkey],
        'size' => $files['size'][$key][$subkey],
      );
    }
    return $requestFiles;
  }

  public static function renderPrice($price, $top = true)
  {
    return (($top) ? '€ ' : '') . iconv('UTF-8', 'UTF-8//IGNORE', number_format($price, 0, ',', '.')) . ((!$top) ? ' €' : '');
  }

}
