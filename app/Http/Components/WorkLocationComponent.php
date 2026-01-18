<?php

namespace App\Http\Components;

use App\Http\Utils\BasicUtil;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;

class WorkLocationComponent
{
     use BasicUtil;
public function getWorkLocations () {
    $created_by  = NULL;
    if(auth()->user()->business) {
        $created_by = auth()->user()->business->created_by;
    }
    $work_locationsQuery = WorkLocation::
    when(empty(auth()->user()->business_id), function ($query) use ( $created_by) {
        $query->when(auth()->user()->hasRole('superadmin'), function ($query)  {
            $query->forSuperAdmin('work_locations');
        }, function ($query) use ( $created_by) {
            $query->forNonSuperAdmin('work_locations', $created_by);
        });
    })
    ->when(!empty(auth()->user()->business_id), function ($query) {
        $query->forBusiness('work_locations');
    })
    ->when(request()->filled("user_id"), function ($query)  {

        $query->whereHas("users" , function($query) {
           $query->whereIn("users.id",[request()->input("user_id")]) ;
        });
    })

        ->when(!empty(request()->search_key), function ($query)  {
            return $query->where(function ($query)  {
                $term = request()->search_key;
                $query->where("work_locations.name", "like", "%" . $term . "%")
                    ->orWhere("work_locations.description", "like", "%" . $term . "%");
            });
        })

        ->when(!empty(request()->start_date), function ($query)  {
            return $query->where('work_locations.created_at', ">=", request()->start_date);
        })
        ->when(!empty(request()->end_date), function ($query)  {
            return $query->where('work_locations.created_at', "<=", (request()->end_date . ' 23:59:59'));
        });

          $work_locations =  $this->retrieveData($work_locationsQuery, "id", "work_locations");

        return $work_locations;


}

}
