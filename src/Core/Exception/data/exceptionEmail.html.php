<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title><?php echo $name ?>: <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></title>
  <style type="text/css">
  body { margin: 0; padding: 20px; margin-top: 20px; background-color: #eee }
  body, td, th { font: 11px Verdana, Arial, sans-serif; color: #333 }
  a { color: #333 }
  h1 { margin: 0 0 0 10px; padding: 10px 0 10px 0; font-weight: bold; font-size: 120% }
  h2 { margin: 0; padding: 5px 0; font-size: 110% }
  ul { padding-left: 20px; list-style: decimal }
  ul li { padding-bottom: 5px; margin: 0 }
  ol { font-family: monospace; white-space: pre; list-style-position: inside; margin: 0; padding: 10px 0 }
  ol li { margin: -5px; padding: 0 }
  ol .selected { font-weight: bold; background-color: #ddd; padding: 2px 0 }
  table.vars { padding: 0; margin: 0; border: 1px solid #999; background-color: #fff; }
  table.vars th { padding: 2px; background-color: #ddd; font-weight: bold }
  table.vars td  { padding: 2px; font-family: monospace; white-space: pre }
  p.error { padding: 10px; background-color: #f00; font-weight: bold; text-align: center; -moz-border-radius: 10px; -webkit-border-radius: 10px; border-radius: 10px; }
  p.error a { color: #fff }
  #main { padding: 20px; padding-left: 70px; border: 1px solid #ddd; background-color: #fff; text-align:left; -moz-border-radius: 10px; -webkit-border-radius: 10px; border-radius: 10px; min-width: 13em; max-width: 52em }
  #message { padding: 10px; margin-bottom: 10px; background-color: #eee; -moz-border-radius: 10px; -webkit-border-radius: 10px; border-radius: 10px; }
  </style>
</head>
<body>
  <center>
  <div id="main">
  <h1>[<?php echo Config::get('MAIN/application') ?>] <?php echo $exception->httpCode ?> | <?php echo $name ?></h1>
  <h2 id="message"><?php echo str_replace("\n", '<br />', htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) ?></h2>
  <h2>stack trace</h2>
  <ul><li><?php echo implode('</li><li>', $traces) ?></li></ul>

  <h2>system</h2>
  <div><?php echo $infoTable ?></div>

  <p id="footer">
    <?php echo Config::get('MAIN/application') ?>
  </p>
  </div>
  </center>
</body>
</html>
