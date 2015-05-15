<?php namespace Alexndr\Shared\Presenters;

use Alexndr\Shared\Http\ResourceController;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
  protected $defer = true;
  protected $configPath = __DIR__.'/config/presenters.php';
  
  public function boot()
  {
    if (function_exists('config_path')) {
      $this->publishes([
       $this->configPath => config_path('presenters.php'),
      ]);
    }
  }
  
  public function register()
  {
    $this->mergeConfigFrom($this->configPath, 'presenters');
    
    $this->app->bind(Presenter::class, function($app)
    {
      if ($route = $app['request']->route()) {
        
        list($class, ) = explode('@', $route->getActionName());
        $controller = $app->make($class);
        
        if ($controller instanceof HasPresenter) {
          $presenter_class = config('presenters.namespace') . '\\' . $controller::getPresenterClass();
          return new $presenter_class;
        }
        
        if ($controller instanceof ResourceController) {
          $model_class = $controller->getModelClassBasename();
          
          if (config('presenters.user_specific_presenters')) {
          
            $user = $app['auth.driver']->user();
            
            if (class_exists($user_presenter_class = $this->getModelPresenterClassName($model_class, $user))) {
              return new $user_presenter_class;
            }
          }
          
          if (class_exists($presenter_class = $this->getModelPresenterClassName($model_class))) {
            return new $presenter_class;
          }
        }
      }
      
      return new DefaultPresenter;
    });
    
    $this->app->singleton(Decorator::class, function()
    {
      return new Decorator;
    });
  }
  
  protected function getModelPresenterClassName($model_class, $user = null)
  {
    $class = config('presenters.namespace');
    
    if ($user) {
      $method = config('presenters.user_acl_method');
      $class .= '\\' . ($user->$method() ? config('presenters.internal_namespace') : config('presenters.external_namespace'));
    }
    
    $class .= "\\{$model_class}Presenter";
    
    return $class;
  }
  
  public function provides()
  {
    return [Presenter::class, Decorator::class];
  }
  
  public static function compiles()
  {
    $mask = __DIR__ . '/%s.php';
    
    return array_map(function($file) use ($mask)
    {
      return sprintf($mask, $file);
    }, ['Presenter', 'DefaultPresenter', 'Decorator', 'HasPresenter', 'ServiceProvider']);
  }
}