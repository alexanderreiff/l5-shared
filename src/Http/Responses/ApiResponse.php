<?php namespace Alexndr\Shared\Http\Responses;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Http\JsonResponse as Response;

class ApiResponse extends Response implements Arrayable, Jsonable
{  
  protected $wrapPayload = false;
  protected $success = false;
  protected $payload = [];
  protected $errorCode;
  protected $errorMsg;
  
  public function __construct($data = null, $status = 200, $headers = array(), $options = 0)
	{
		parent::__construct(null, $status, $headers, $options);
		
		if ($data instanceof Arrayable) {
  		$data = $data->toArray();
		}
		
		if ($error = $this->dataIsError($data)) {
  		$this->setError(0, $error, $status);
		} else {
  		$this->addPayload($data, null, $this->isSuccessful());
		}		
	}
  
  public function addPayload($key, $value = null, $make_success = true)
  {
    if ($key instanceof Arrayable) {
      $this->payload = $key->toArray();
    } else if (is_array($key)) {
      $this->payload = $key;
    } else {
      $this->payload[$key] = $value;
    }
    
    if ($make_success) {
      $this->makeSuccess();  
    } else {
      // Update data gets called in ::makeSuccess, so only need to call
      // if we aren't calling that
      $this->updateData();
    }
    
    return $this;
  }
  
  public function getPayload($key = null)
  {
    if (! empty($this->payload)) {
      return is_null($key) || $key === true ? $this->payload : @$this->payload[$key];
    }
    
    return null;
  }
  
  public function isSuccess()
  {
    return $this->success;
  }
  
  public function makeSuccess($status_code = self::HTTP_OK)
  {
    return $this->setSuccess(true, $status_code);
  }
  
  public function makeFailure($status_code = self::HTTP_INTERNAL_SERVER_ERROR)
  {
    return $this->setSuccess(false, $status_code); 
  }
  
  public function setSuccess($success, $status_code = false)
  {
    $this->success = $success;
    
    if ($status_code) {
      $this->setStatusCode($status_code);
    }
    
    $this->updateData();
    
    return $this;
  }
  
  public function setError($code, $message, $status_code = self::HTTP_INTERNAL_SERVER_ERROR)
  {
    if ($code instanceof Exception) {
      list($code, $message) = [$code->getCode(), $code->getMessage()];
    }
    
    if ($code) {
      $this->errorCode = $code;
    }
    
    if ($message) {
      $this->errorMsg = $message;
    }
    
    return $this->makeFailure($status_code);
  }
  
  public function setErrorCode($code)
  {
    return $this->setError($code, null);
  }
  
  public function setErrorMessage($message)
  {
    return $this->setError(null, $message);
  }
  
  public function getError()
  {
    $error = [];
    
    if ($code = $this->errorCode) {
      $error['code'] = $code;
    }
    
    if ($message = $this->errorMsg) {
      $error['message'] = $message;      
    }
    
    if (! empty($error)) {
      return $error;
    }
    
    return null;
  }
  
  public function toArray()
  {
    if (! $this->wrapPayload) {
      return $this->getPayload();
    }
    
    $out = ['success' => $this->success];
    
    if ($payload = $this->getPayload()) {
      $out['data'] = $payload;
    }
    
    if ($error = $this->getError()) {
      $out['error'] = $error;
    }
    
    return $out;
  }
  
  public function unwrap()
  {
    return $this->wrap(false);
  }
  
  public function wrap($wrap = true)
  {
    $this->wrapPayload = $wrap;
    $this->updateData();
    return $this;
  }
  
  public function toJson($options = 0)
  {
    return json_encode($this->toArray(), $options);
  }
  
  protected function updateData()
  {
    $this->setData($this);
  }
  
  protected function dataIsError($data)
  {
    if (! is_array($data)) {
      return false;
    }
    
    $keys = array_keys($data);
    
    if (count($keys) === 1 && $keys[0] === 'error') {
      return $data['error'];
    }
    
    return false;
  }
}