<?php namespace Alexndr\Shared\Cdn;

class EdgeCastSignedUrl extends SignedUrl
{  
  protected $baseUrl;
  protected $privateKey;
  protected $expires;
  protected $ip;
  protected $lockUrl;
  protected $extOptions;
  
  protected static $execPath;
  protected static $ecMapping = [];
  protected static $ecOptions = [];
  
  public static $defaults;
  
  public function __construct($base_url, $expires = 0, $ip = null, $lock_url = false, $private_key = null, array $ext_options = [])
  {
    $this->baseUrl = $base_url;
    $this->privateKey = ($private_key ?: static::$defaults['private_key']);
    $this->expires = $this->getExpirationTime($expires);
    $this->ip = $ip;
    $this->lockUrl = $lock_url ? parse_url($base_url, PHP_URL_PATH) : null;
    $this->extOptions = $this->getExtraOptions($ext_options);
  }
  
  public function getUrl()
  {
    $args = array_merge($this->getBaseOptions(), $this->extOptions);
    
    return $this->baseUrl . '?' . $this->generateToken($args);
  }
  
  protected function getExecutablePath()
  {
    if (empty(static::$execPath)) {
      
      $is_64 = 64 === 8 * PHP_INT_SIZE;
      static::$execPath = __DIR__.'/bin/ec_encrypt' . ($is_64 ? '64' : '');
    }
    
    return static::$execPath;
  }
  
  protected function getBaseOptions()
  {
    if (empty(static::$ecMapping)) {
      static::$ecMapping = [
        'ip' => 'ec_clientip',
        'expires' => 'ec_expires',
        'lockUrl' => 'ec_url_allow',
      ];
    }
    
    $opts = [];
    
    foreach (static::$ecMapping as $prop => $ec_key) {
      if (! empty($value = $this->$prop)) {
        $opts[$ec_key] = $value;
      }
    }
    
    return $opts;
  }
  
  protected function getExtraOptions($options)
  {
    if (empty(static::$ecOptions)) {
      static::$ecOptions = ['ec_clientip', 'ec_country_allow', 'ec_country_deny', 'ec_expire', 'ec_proto_allow', 'ec_proto_deny',
                            'ec_ref_allow', 'ec_ref_deny', 'ec_prebuf', 'ec_rate', 'ec_url_allow',
                            ];
    }
    return array_only($options, static::$ecOptions);
  }
  
  protected function generateToken($args)
  {
    $cmd = [$this->getExecutablePath(), $this->privateKey, addcslashes(urldecode(http_build_query($args)), '&')];
    
    exec(implode(' ', $cmd), $output);
    
  	return $output[0];
  }
}