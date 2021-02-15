<?php

namespace PeterVincze\AbTesting\Middleware;

use Closure;
use Illuminate\Http\Request;
use PeterVincze\AbTesting\AbTesting;

class AbTestingProxy
{
    public function handle(Request $request, Closure $next)
    {
		$abtesting = app('ab-testing');
		$experiment = $abtesting->getExperiment() ?? $abtesting->pageView();
		if (!empty($experiment->git_checkout) && !empty($experiment->git_repo)) {
            require base_path() .'/ab-testing/' . $experiment->id . '/public/index.php';
	    }
	    else {
	    	return $next($request);
		}
    }
}
