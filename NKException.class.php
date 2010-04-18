<?php

/**
 * NKException - exception class
 * 
 * @uses Exception
 * @package default
 * @version $id$
 * @copyright 
 * @author Wojciech Sznapka <wojciech@sznapka.pl> 
 * @license 
 */
class NKException extends Exception
{
  const LOGIN_FAILED              = 1;
  const PROXY_SOURCE_NOT_READABLE = 2;
  const CURL_ERROR                = 4;
  const CURL_STATUS_CODE          = 8;

  /**
   * possible exception messages 
   * 
   * @var array
   * @access private
   */
  private $messages = array(
    self::LOGIN_FAILED              => 'Logowanie do serwisu nasza-klasa.pl nie powiodło się, proszę wprowadzić poprawny login i hasło w preferencjach widgetu',
    self::PROXY_SOURCE_NOT_READABLE => 'Nie mogę odczytać źródła proxy',
    self::CURL_ERROR                => 'Problem z połączeniem do nasza-klasa.pl',
    self::CURL_STATUS_CODE          => 'Połączenie do nasza-klasa.pl zwrociło nieoczekiwany status',
  );

  /**
   * overriden constructor - if there's no message it will take it from messages array 
   * 
   * @param mixed $message 
   * @param int $code 
   * @access public
   * @return void
   */
  public function __construct($message, $code = 0, Exception $previous = null, $messageParts= array(), $logInfo = '') 
  {  
    $message = isset($this->messages[$code]) ? $this->messages[$code] : $message;
    $message = vsprintf($message, $messageParts);
    parent::__construct($message, $code);
    $this->log($logInfo);
  }

  /**
   * logs exception message to file 
   * 
   * @param mixed $message 
   * @access protected
   * @return void
   */
  protected function log($message = '')
  {
    $msg = sprintf('%s [%s] %s', strftime('%b %d %H:%M:%S'), get_class($this), $this->getMessage());
    if (strlen($message)) {
      $msg .= ', INFO: ' . $message;
    }
    $fp  = fopen('data/error.log', 'a');
    flock ($fp, LOCK_EX);
    fwrite($fp, $msg . PHP_EOL);
    flock ($fp, LOCK_UN);
    fclose($fp);
  }
}
