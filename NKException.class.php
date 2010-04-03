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
  const LOGIN_FAILED = 1;

  /**
   * possible exception messages 
   * 
   * @var array
   * @access private
   */
  private $messages = array(
    self::LOGIN_FAILED => 'Logowanie do serwisu nasza-klasa.pl nie powiodło się, proszę wprowadzić poprawny login i hasło w preferencjach widgetu',
  );

  /**
   * overriden constructor - if there's no message it will take it from messages array 
   * 
   * @param mixed $message 
   * @param int $code 
   * @access public
   * @return void
   */
  public function __construct($message, $code = 0) 
  {  
    $message = isset($this->messages[$code]) ? $this->messages[$code] : $message;
    parent::__construct($message, $code);
  } 

}
