<?php namespace Alexndr\Shared\Support;

trait SharesSingleton
{
  protected static $instance = null;
  
  public static function getInstance()
  {
    return static::$instance ?: static::$instance = new static;
  }
}