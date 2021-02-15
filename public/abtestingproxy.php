<?php
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| Check If Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is maintenance / demo mode via the "down" command we
| will require this file so that any prerendered template can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (mb_strpos(__DIR__,'/abtesting/')===false) {
	if (file_exists(__DIR__.'/../storage/framework/maintenance.php')) {
	    require __DIR__.'/../storage/framework/maintenance.php';
	}

	require __DIR__.'/../vendor/autoload.php';

	$app = new Illuminate\Foundation\Application(
	    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
	);

	$app->singleton(
	    Illuminate\Contracts\Http\Kernel::class,
	    PeterVincze\AbTesting\Kernel::class
	);

	$app->singleton(
	    Illuminate\Contracts\Debug\ExceptionHandler::class,
	    App\Exceptions\Handler::class
	);

	$kernel = $app->make(Kernel::class);
	$response = tap($kernel->handle(
    	$request = Request::capture()
	))->send();
}
if (empty(app('ab-testing')->getExperiment()->git_repo) || mb_strpos(__DIR__,'/abtesting/')!==false) {
	require dirname(__DIR__) . '/index.php';
}