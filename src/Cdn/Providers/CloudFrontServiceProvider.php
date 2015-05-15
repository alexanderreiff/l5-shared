<?php namespace Alexndr\Shared\Cdn\Providers;

use Alexndr\Shared\Cdn\CloudFrontSignedUrl as Url;
use Illuminate\Support\ServiceProvider;

class CloudFrontServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
  	if (function_exists('config_path')) {
    	$path = realpath(__DIR__.'/../config/cloudfront.php');
    	
    	$this->publishes([
       $path => config_path('cloudfront.php'),
      ]);
    }
    
		Url::setDefaults($this->app['config']['cloudfront']);
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