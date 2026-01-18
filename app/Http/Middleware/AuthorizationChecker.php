<?php

namespace App\Http\Middleware;

use App\Http\Utils\BasicUtil;
use App\Http\Utils\ModuleUtil;
use App\Models\BusinessSubscription;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class AuthorizationChecker
{
    use ModuleUtil, BasicUtil;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        $user = auth()->user();

        $business = $user->business;

        if(!empty($business) && $business->owner_id != $user->id){
            if($user->hasRole(("business_employee#" . $business->id))) {
            $moduleEnabled =  $this->isModuleEnabled("employee_login", false);
            // if(!$moduleEnabled){
            //     // return response(['message' => 'Module is not enabled'], 401);
            // }

            }
        }

        if(empty($user->email_verified_at)) {
            return response(['message' => 'please activate your email'], 401);
        }


        if(empty($user->is_active)) {
            return response(['message' => 'User not active'], 401);
        }




        return $next($request);
    }

}
