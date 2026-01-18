<?php

namespace App\Http\Middleware;

use App\Http\Utils\BasicEmailUtil;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ConfigureMailSettings
{
    use BasicEmailUtil;
   /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $this->emailConfigure();
        
        return $next($request);
    }
}
