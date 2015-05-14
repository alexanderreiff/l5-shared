<?php namespace Alexndr\Shared\Http\Responses;
  
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
  public function register()
  {
    $this->app->bind(
  		'Illuminate\Contracts\Routing\ResponseFactory',
  		'Alexndr\Shared\Http\Responses\ResponseFactory'
		);
  }
  
  
  public static function compiles()
  {
    $mask = __DIR__ . '/%s.php';
    
    return array_map(function($file) use ($mask)
    {
      return sprintf($mask, $file);
    }, ['ApiResponse', 'ResponseFactory', 'ServiceProvider']);
  }
}