<?php namespace Alexndr\Shared\Cdn\Providers;

use Alexndr\Shared\Cdn\EdgeCastSignedUrl as Url;
use Illuminate\Support\ServiceProvider;

class EdgeCastServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
  	if (function_exists('config_path')) {
    	$path = realpath(__DIR__.'/../config/edgecast.php');
    	
    	$this->publishes([
       $path => config_path('edgecast.php'),
      ]);
    }
    
		Url::setDefaults($this->app['config']['edgecast']);
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
	}

}