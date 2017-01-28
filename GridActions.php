<?php
/**
 * Created by PhpStorm.
 * User: p3ym4n
 * Date: 11/4/2016 AD
 * Time: 11:47
 */

namespace p3ym4n\GridView;


use Illuminate\Http\Request;

trait GridActions {
	
	
	private function check(Request $request)
	{
		$this->validate($request, [
			'id' => 'bail|required|array|distinct',
		], [
			'id.required' => 'ابتدا چند ردیف را انتخاب کنید.',
			'id.array'    => 'شماره ردیف ها همگی باید در قالب یک آرایه ارسال شوند.',
			'id.distinct' => 'ردیف های انتخاب شده تکراری هستند.',
		]);
	}
	
	public function bulkActions(Request $request)
	{
		if ($action = $request->input('action')) {
			
			$method = "grid" . studly_case($action);
			
			if (method_exists($this, $method)) {
				
				$this->check($request);
				
				return $this->{$method}($request, $request->input('id'));
			}
			
			throw new NotFoundHttpException(trans('messages.the requested page not found'));
		}
		
	}
	
}