<?php namespace Alexndr\Shared\Presenters\Exceptions;
  
use Exception;
use LogicException;

class FieldNotPresentedException extends LogicException
{  
  public function __construct($message = '', $code = 0, Exception $previous = null)
  {
    $message = "A presenter for field $message is not defined by the presenter.";
    
    parent::__construct($message, $code, $previous);
  }
}