<?php namespace Alexndr\Shared\Presenters;

use Alexndr\Shared\Presenters\Exceptions\ObjectCannotBeDecoratedException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator as Paginator;
use Illuminate\Support\Collection;

class Decorator
{  
  public function decorate($object, Presenter $presenter)
  {
    if ($object instanceof Model) {
      return $this->model($object, $presenter);
    }
    
    if ($object instanceof Collection) {
      return $this->collection($object, $presenter);
    }
    
    if ($object instanceof Paginator) {
      return $this->paginator($object, $presenter);
    }
    
    throw new ObjectCannotBeDecoratedException(get_class($object));
  }
  
  public function model(Model $model, Presenter $presenter)
  {
    return with(clone $presenter)->set($model)->toArray();  
  }
  
  public function collection(Collection $collection, Presenter $presenter)
  {
    foreach($collection as $key => $value) {
      $collection->put($key, $this->model($value, $presenter));
    }
   
    return $collection;
  }
  
  public function paginator(Paginator $paginator, Presenter $presenter)
  {
    $items = [];
   
    foreach($paginator->items() as $key => $item) {
      $paginator->put($key, $this->model($item, $presenter));
    }
   
    return $paginator;
  }
}