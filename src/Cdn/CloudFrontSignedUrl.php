<?php namespace Alexndr\Shared\Cdn;

class CloudFrontSignedUrl extends SignedUrl {
  
  protected $baseUrl;
  protected $privateKey;
  protected $keyPairId;
  protected $ip;
  protected $starts;
  protected $expires;
  protected $policy;
  protected $policyJson;
  
  protected static $privateKeys = [];
  
  public function __construct($base_url, $expires = 0, $ip = null, $starts = null, $private_key = null, $key_pair_id = null)
  {
    $this->baseUrl = $base_url;
    $this->privateKey = base_path() . '/resources/certs/' . ($private_key ?: static::$defaults['private_key']);
    $this->keyPairId = $key_pair_id ?: static::$defaults['key_pair_id'];
    $this->expires = time() + ($expires ?: 300);
    $this->ip = $ip;
    $this->starts = $starts;
  }
  
  public function getUrl()
  {
    $policy_json = $this->getPolicy(true);
    $encoded_policy = $this->urlSafeBase64Encode($policy_json);
    $signature = $this->signPolicy();
    $encoded_signature = $this->urlSafeBase64Encode($signature);
    
    $result = $this->baseUrl;
    $separator = strpos($this->baseUrl, '?') == FALSE ? '?' : '&';    
    $result .= $separator . 'Policy=' . $encoded_policy . '&Signature=' . $encoded_signature . "&Key-Pair-Id=" . $this->keyPairId;
    
    return $result;
  }
  
  public function getUrlForAdobe()
  {
    return $this->encodeQueryParams($this->getUrl());
  }
  
  public function getPolicy($as_json = false)
  {
    if (is_null($this->policy)) {
      $this->policy = [
        'Statement' => [[
          'Resource' => $this->baseUrl,
          'Condition' => [
            'DateLessThan' => ['AWS:EpochTime' => $this->expires]
          ]
        ]]
      ];
      
      if (! is_null($this->ip)) {
        $this->policy['Statement'][0]['Condition']['IpAddress'] = ['AWS:SourceIp' => $this->ip . '/32'];
      }
      
      if (! is_null($this->starts)) {
        $this->policy['Statement'][0]['Condition']['DateGreaterThan'] = ['AWS:EpochTime' => $this->starts];
      }
    }
    
    return $as_json ? $this->jsonEncodePolicy() : $this->policy;
  }
  
  protected function signPolicy()
  {
    $md5 = md5($this->privateKey);
    $signature = '';
    
    if (! isset(self::$privateKeys[$md5])) {
      $fp = fopen($this->privateKey, 'r');
      $priv_key = fread($fp, 8192);
      fclose($fp);
      
      $pkeyid = openssl_pkey_get_private($priv_key);
      self::$privateKeys[$md5] = $pkeyid;
    }
    
    openssl_sign($this->getPolicy(true), $signature, self::$privateKeys[$md5]);
    return $signature;
  }
  
  protected function jsonEncodePolicy()
  {
    if (is_null($this->policyJson)) {
      $this->policyJson = json_encode($this->policy);
    }
    
    return $this->policyJson;
  }
  
  protected function urlSafeBase64Encode($value)
  {
    $encoded = base64_encode($value);
    // replace unsafe characters +, = and / with the safe characters -, _ and ~
    return str_replace(
      ['+', '=', '/'],
      ['-', '_', '~'],
      $encoded
    );
  }
  
  protected function encodeQueryParams($stream_name)
  {
    return str_replace(
      ['?', '=', '&'],
      ['%3F', '%3D', '%26'],
      $stream_name
    );
  }
}