<?php namespace Alexndr\Shared\Cdn;
  
abstract class SignedUrl
{  
  protected static $defaults;
  
  abstract public function getUrl();
  
  public function getExpirationTime($expires)
  {
    if (empty($expires)) {
      return 0;
    }
    
    if (strpos($expires, '+') === 0) {
      return time() + (int) ltrim($expires, '+');
    }
    
    return $expires;
  }
  
  public function __toString()
  {
    return $this->getUrl();
  }
  
  public static function setDefaults($defaults)
  {
    static::$defaults = $defaults;
  }
  
  public static function getBaseMediaUrl($type)
  {
    return array_get(static::$defaults, "base_url.$type");
  }
}