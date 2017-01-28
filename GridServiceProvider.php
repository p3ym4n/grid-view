<?php

namespace p3ym4n\GridView;

use Illuminate\Support\ServiceProvider;

class GridServiceProvider extends ServiceProvider {
	
	protected $defer = true;
	const NAME = 'grid';
	
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		
		$this->loadViewsFrom(__DIR__ . '/Views', static::NAME);
		
		$this->loadTranslationsFrom(__DIR__ . '/Translations', static::NAME);
		
		$this->publishes([
			__DIR__ . '/Translations' => resource_path('lang/vendor/' . static::NAME),
			__DIR__ . '/Views'        => resource_path('views/vendor/' . static::NAME),
		]);
		
	}
	
	/**
	 * Register the application services.
	 *
	 * @return void
	 */
//	public function register()
//	{
//
//		$this->app->bind(Grid::class, function () {
//			return new Grid();
//		});
//	}
	
	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
//	public function provides()
//	{
//		return [Grid::class];
//	}
	
	
}
