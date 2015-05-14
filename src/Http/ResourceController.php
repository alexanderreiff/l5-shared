<?php namespace Alexndr\Shared\Http;

use Closure;
use Alexndr\Shared\Http\Responses\ApiResponse;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ResourceController extends Controller
{  
  use ValidatesRequests;
  
  const PER_PAGE = 50;

  protected $request;
  protected $app;
  protected $apiResponse;
  protected $modelNamespace;
  protected $modelClass;
  protected $modelClassBasename;
  protected $eagerLoadable = [];
  protected $columns = ['*'];
  protected $modelKey = 'model';
  
  public function __construct(Request $request, Application $app)
  {
    $this->request = $request;
    $this->app = $app;
    $this->apiResponse = new ApiResponse;  
    $this->resolveModelNames();
  }
  
	public function index()
	{
  	$query = $this->baseGetQuery();
  	
  	if (is_numeric(static::PER_PAGE)) {
  		return $query->paginate(static::PER_PAGE, $this->columns);
		}
		
		return $query->get($this->columns);
	}
	
	public function show($id)
	{
		return $this->baseGetQuery()->findOrFail($id);
	}
	
	public function store()
	{
		return $this->update(null);
	}
	
	public function update($id)
	{
	  $data = $this->request->all();
  	  
	  return $this->attemptPersistence(function() use ($id, $data)
	  {
  	  $model = $this->getModel($id); 
      $model->fill($data);
  	  if ($model->save()) {
        $this->apiResponse->addPayload($this->modelKey, $model);
      }
    });
	}
	
	public function destroy($id)
	{
  	return $this->attemptPersistence(function() use ($id)
  	{
      $model = $this->getModel($id);
  	  if ($model->delete()) {
        $this->apiResponse->makeSuccess();
      }
    });
	}
	
	public function getModelName()
	{
  	return $this->modelClass;
	}
	
	public function getModelClassBasename()
	{
  	return $this->modelClassBasename;
	}
  
  protected function attemptPersistence(Closure $closure)
  {
    try {
      call_user_func($closure);
    } catch (Exception $e) {
      $this->apiResponse->setError($e);
    }
    return $this->apiResponse;
  }
  
  protected function getModel($id)
  {
    return $id ? call_user_func_array($this->modelClass.'::find', [$id]) : new $this->modelClass;
  }
  
  protected function baseGetQuery()
  {
    return call_user_func_array($this->modelClass.'::with', $this->eagerLoadable ?: [[]]);
  }
  
  protected function resolveModelNames()
  {
    if ($this->modelClassBasename == null) {
      $bn = class_basename(get_class($this));
      $this->modelClassBasename = str_replace('Controller', '', $bn);
    }
    
    if ($this->modelClass == null) {
      $this->modelClass = $this->modelNamespace . '\\' . $this->modelClassBasename;
    }
  }
}