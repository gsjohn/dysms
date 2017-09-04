<?php

namespace GsJohn\Dysms;

use Illuminate\Support\ServiceProvider;

class DysmsProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
	    $this->publishes([
		    __DIR__.'/config/dysms.php' => config_path('dysms.php')
	    ], 'config');

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
	    //
	    $this->app->singleton('dysms', function () {
		    return $this->app->make('GsJohn\Dysms\DysmsHelper');
	    });
    }
}
