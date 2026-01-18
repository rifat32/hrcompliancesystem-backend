<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessTimesUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Business;
use App\Models\BusinessTime;
use App\Models\Department;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessTimesController extends Controller
{
    use ErrorUtil,BusinessUtil;
    /**
     *
     * @OA\Patch(
     *      path="/v1.0/business-times",
     *      operationId="updateBusinessTimes",
     *      tags={"business_times_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update business times",
     *      description="This method is to update business times",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"business_id","times"},
     *    @OA\Property(property="times", type="string", format="array",example={
    *{"day":0,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
    *{"day":1,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
    *{"day":2,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":3,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
    *{"day":4,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
    *{"day":5,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
    *{"day":6,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true}
     *
     * }),

     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function updateBusinessTimes(BusinessTimesUpdateRequest $request)
    {
        try {

            return  DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('business_times_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();

                $business = Business::where([
                    "id" => auth()->user()->business_id
                ])
                ->first();

                $timesArray = collect($request_data["times"])->unique("day");


                // $conflicted_work_shift_ids = collect();
                // foreach($timesArray as $business_time) {
                //     $work_shift_ids = WorkShift::where([
                //         "business_id" => auth()->user()->business_id
                //     ])
                //     ->whereHas('details', function ($query) use ($business_time) {
                //         $query->where('work_shift_details.day',($business_time["day"]))
                //         ->when(!empty($time["is_weekend"]), function($query) {
                //             $query->where('work_shift_details.is_weekend',1);
                //         })
                //         ->where(function($query) use($business_time) {
                //             $query->whereTime('work_shift_details.start_at', '<=', ($business_time["start_at"]))
                //                   ->orWhereTime('work_shift_details.end_at', '>=', ($business_time["end_at"]));

                //         });
                //     })
                //     ->pluck("id");
                //     $conflicted_work_shift_ids = $conflicted_work_shift_ids->merge($work_shift_ids);

                // }
                // $conflicted_work_shift_ids = $conflicted_work_shift_ids->unique()->values()->all();

                // if(!empty($conflicted_work_shift_ids)) {
                //     WorkShift::whereIn("id",$conflicted_work_shift_ids)->update([
                //         "is_active" => 0
                //     ]);
                //     WorkShiftHistory::where([
                //         "to_date" => NULL
                //     ])
                //     ->whereIn("work_shift_id",$conflicted_work_shift_ids)

                //     ->update([
                //         "to_date" => now()
                //     ]);

                //     // $default_work_shift_data = [
                //     //     'name' => 'Default work shift',
                //     //     'type' => 'regular',
                //     //     'description' => '',
                //     //     'is_personal' => false,
                //     //     'break_type' => 'unpaid',
                //     //     'break_hours' => 1,

                //     //     'details' => $business->times->toArray(),
                //     //     "is_business_default" => 1,
                //     //     "is_active",
                //     //     "is_default" => 1,
                //     //     "business_id" => $business->id,
                //     // ];

                //     // $default_department =   Department::where("business_id",auth()->user()->business_id)->whereNull("parent_id")->first();

                //     // $default_work_shift = WorkShift::create($default_work_shift_data);
                //     // $default_work_shift->details()->createMany($default_work_shift_data['details']);
                //     // $default_work_shift->departments()->sync([$default_department->id]);


                //     // $employee_work_shift_history_data = $default_work_shift->toArray();
                //     // $employee_work_shift_history_data["work_shift_id"] = $default_work_shift->id;
                //     // $employee_work_shift_history_data["from_date"] = $business->start_date;
                //     // $employee_work_shift_history_data["to_date"] = NULL;
                //     //  $employee_work_shift_history =  WorkShiftHistory::create($employee_work_shift_history_data);
                //     //  $employee_work_shift_history->details()->createMany($default_work_shift_data['details']);
                //     //  $employee_work_shift_history->departments()->sync([$default_department->id]);
                // }


              BusinessTime::where([
                "business_id" => auth()->user()->business_id
               ])
               ->delete();
               foreach($timesArray as $business_time) {
                BusinessTime::create([
                    "business_id" => auth()->user()->business_id,
                    "day"=> $business_time["day"],
                    "start_at"=> $business_time["start_at"],
                    "end_at"=> $business_time["end_at"],
                    "is_weekend"=> $business_time["is_weekend"],
                ]);

               }





                return response(["message" => "data inserted"], 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


     /**
        *
     * @OA\Get(
     *      path="/v1.0/business-times",
     *      operationId="getBusinessTimes",
     *      tags={"business_times_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="This method is to get business times ",
     *      description="This method is to get business times",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessTimes(Request $request) {
        try{




            $business_times = BusinessTime::where([
                "business_id" => auth()->user()->business_id
            ])->orderByDesc("id")->get();
            return response()->json($business_times, 200);
        } catch(Exception $e){

        return $this->sendError($e);
        }
    }
}
