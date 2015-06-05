<?php namespace Alexndr\Shared\Presenters;
  
use ArrayAccess;
use BadMethodCallException;
use Alexndr\Shared\Presenters\Exceptions\FieldNotPresentedException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

abstract class Presenter implements ArrayAccess
{  
  protected $object;
  protected $hidden = [];
  protected $visible = [];
  
  public function __construct($object = null) {
    if (! is_null($object)) {
      $this->set($object);
    }
  }
  
  public function set($object)
  {
    $this->object = $object;
    return $this;
  }
  
  public function raw()
  {
    return $this->object;
  }
  
  public function __get($key)
  {
    try {
      return $this->present($key);
    } catch (FieldNotPresentedException $e) { } 
   
    return $this->object->$key;
  }
  
  public function __call($method, $args)
  {
    if (method_exists($this->object, $method)) {
      return call_user_func_array([$this->object, $method], $args);
    }
    
    $class = get_called_class();
    throw new BadMethodCallException("Call to undefined method {$class}::{$method}()");
  }
  
  public function offsetExists($key)
  {
    return isset($this->object[$key]);
  }
  
  public function offsetGet($key)
  {
    return $this->object[$key];
  }
  
  public function offsetSet($key, $value)
  {
    $this->object[$key] = $value;
  }
  
  public function offsetUnset($key)
  {
    unset($this->object[$key]);
  }
  
  public function toArray()
  {
    $arr = $this->object->toArray();
    
    if (! empty($this->visible)) {
      $arr = array_filter($arr, function($key)
      {
        return in_array($key, $this->visible);
      }, ARRAY_FILTER_USE_KEY);
      
    } else if (! empty($this->hidden)) {
      $arr = array_filter($arr, function($key)
      {
        return ! in_array($key, $this->hidden);
      }, ARRAY_FILTER_USE_KEY);
    }
    
    $keys = ! empty($this->visible) ? $this->visible : array_keys($arr);
    
    foreach ($keys as $key) {
      try {
        $arr[$key] = $this->present($key);
      } catch (FieldNotPresentedException $e) { }
    }
    
    return $arr;
  }
  
  public function toJson($options = 0)
  {
    return json_encode($this->toArray(), $options);
  }
  
  public function __toString()
  {
    return $this->toJson();
  }
  
  protected function present($field)
  {
    if (method_exists($this, $cc_key = camel_case($field))) {
      $presented = $this->{$cc_key}();
      
      return $this->hasSecondaryPresenter($presented) 
              ? $this->decorate(...$presented) 
              : $presented;
    }
    
    throw new FieldNotPresentedException($field);
  }
  
  protected function idsAndNames($field)
  {
    if (($records = $this->object->$field) instanceof Arrayable) {
      $records = $records->toArray();
    }
    
    return array_map(function($record)
    {
      return array_only($record, ['id', 'name']);
    }, $records);
  }
  
  protected function hasSecondaryPresenter($presented)
  {
    return is_array($presented) && count($presented) == 2 && $presented[1] instanceof Presenter;
  }
  
  protected function decorate($presented, Presenter $presenter)
  {
    return app(Decorator::class)->decorate($presented, $presented);
  }
}