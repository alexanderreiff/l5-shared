<?php namespace Alexndr\Shared\Presenters\Exceptions;
  
use Exception;
use LogicException;

class ObjectCannotBeDecoratedException extends LogicException
{  
  public function __construct($message = '', $code = 0, Exception $previous = null)
  {
    $message = "Objects of type $message cannot be decorated.";
    
    parent::__construct($message, $code, $previous);
  }
}