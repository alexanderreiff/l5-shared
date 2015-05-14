<?php namespace Alexndr\Shared\Http\Responses;

use Illuminate\Routing\ResponseFactory as BaseFactory;

class ResponseFactory extends BaseFactory
{  
  public function json($data = [], $status = 200, array $headers = [], $options = 0)
	{
		return new ApiResponse($data, $status, $headers, $options);
	}
}