<?php

use App\Http\Controllers\CodeGeneratorController;
use App\Http\Controllers\CustomWebhookController;
use App\Http\Controllers\SetUpController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\DeveloperLoginController;
use App\Http\Controllers\UpdateDatabaseController;
use App\Mail\SendPasswordMail;

use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get("/developer-login", [DeveloperLoginController::class, "login"])->name("login.view");
Route::post("/developer-login", [DeveloperLoginController::class, "passUser"]);

Route::get('/test-email', function () {
    try {
        // Get a user for testing purposes
        $user = User::find(1); // Replace with a valid user ID
        $password = 'testPassword123'; // Test password

        // Attempt to send the email
        Mail::to("rifatbilalphilips@gmail.com")->send(new SendPasswordMail($user, $password));

        return 'Test email sent successfully to rifatbilalphilips@gmail.com!';
    } catch (\Exception $e) {
        // Log the error message
        Log::error('Mail sending failed: ' . $e->getMessage());

        return 'Failed to send email. Check logs for details.';
    }
});


Route::get('/code-generator', [CodeGeneratorController::class, "getCodeGeneratorForm"])->name("code-generator-form");
Route::post('/code-generator', [CodeGeneratorController::class, "generateCode"])->name("code-generator");

Route::get('/send-test-mail', function () {
    $user = (object) [
        'email' => 'rifatbilalphilips@gmail.com',
        'name' => 'Test User'
    ];


    Mail::send([], [], function ($message) use ($user) {
        $message->to($user->email)
            ->subject('Test Mail')
            ->setBody("
                    <h1>Hello, {$user->name}</h1>

                    <p>This is a test email sent to rifatbilalphilips@gmail.com</p>
                ", 'text/html');
    });

    return "Test email sent!";
});
// Grouping the routes and applying middleware to the entire group
Route::middleware(['developer'])->group(function () {

    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/activity-log/{id}', [SetUpController::class, "testActivity"])->name("api-call");

    Route::get('/activity-log/{id}', [SetUpController::class, "testApi"])->name("api-test");


    Route::get('/custom-test-api', function () {
        return view("test_api_custom");
    })->name("custom_api_test");




    Route::get('/activity-log', [SetUpController::class, "getActivityLogs"])->name("activity-log");

    Route::get('/setup', [SetUpController::class, "setUp"])->name("setup");

    Route::get('/backup', [SetUpController::class, "backup"])->name("backup");

    Route::get('/roleRefresh', [SetUpController::class, "roleRefresh"])->name("roleRefresh");

    Route::get('/swagger-refresh', [SetUpController::class, "swaggerRefresh"]);

    Route::get('/migrate', [SetUpController::class, "migrate"]);

    Route::get('/cron-job', [SetUpController::class, "cronJob"]);
      Route::get('/artisan/{command}', [SetUpController::class, "artisanCommand"]);



    Route::get('/configure-stripe', [SetUpController::class, "configureStripe"]);


});





Route::get("/subscriptions/redirect-to-stripe", [SubscriptionController::class, "redirectUserToStripe"]);

Route::get("/subscriptions/get-success-payment", [SubscriptionController::class, "stripePaymentSuccess"])->name("subscription.success_payment");
Route::get("/subscriptions/get-failed-payment", [SubscriptionController::class, "stripePaymentFailed"])->name("subscription.failed_payment");


Route::get("/subscriptions/redirect-to-stripe/renewal", [SubscriptionController::class, "redirectUserToStripeRenewal"]);

Route::get("/subscriptions/get-success-payment/renewal", [SubscriptionController::class, "stripeRenewPaymentSuccess"])->name("subscription.success_renewal");
Route::get("/subscriptions/get-failed-payment/renewal", [SubscriptionController::class, "stripeRenewPaymentFailed"])->name("subscription.failed_renewal");



Route::get("/database-update", [UpdateDatabaseController::class, "updateDatabase"]);
Route::get("/module-update", [UpdateDatabaseController::class, "updateModule"]);



Route::get("/db-operation", [UpdateDatabaseController::class, "dbOperation"] );
Route::get("/db-operation-v2", [UpdateDatabaseController::class, "dbOperationV2"] );
Route::get("/db-operation-v3", [UpdateDatabaseController::class, "dbOperationV3"] );
Route::get("/db-operation-v4", [UpdateDatabaseController::class, "dbOperationV4"] );
Route::get("/db-operation-v5", [UpdateDatabaseController::class, "dbOperationV5"] );
Route::get("/db-operation-v6", [UpdateDatabaseController::class, "dbOperationV6"] );
Route::get("/db-operation-v7", [UpdateDatabaseController::class, "dbOperationV7"] );

Route::get("/db-operation-v8", [UpdateDatabaseController::class, "dbOperationV8"] );

Route::get("/db-operation-v9", [UpdateDatabaseController::class, "dbOperationV9"] );

Route::get("/db-operation-v10", [UpdateDatabaseController::class, "dbOperationV10"] );


Route::get("/edit-migration", [UpdateDatabaseController::class, "editMigration"] );


Route::get("/file-update", [UpdateDatabaseController::class, "updateDatabaseFilesForBusiness"]);


Route::get("/activate/{token}", function (Request $request, $token) {
    $user = User::where([
        "email_verify_token" => $token,
    ])
        ->where("email_verify_token_expires", ">", now())
        ->first();

    if (!$user) {
        return response()->json([
            "message" => "Invalid URL or URL expired",
        ], 400);
    }

    // Mark the email as verified
    $user->email_verified_at = now();
    $user->save();

    return view("email.welcome", [
        'title' => $user->title,
        'first_name' => $user->first_Name,
        'middle_name' => $user->middle_Name,

        'last_name' => $user->last_Name,
        'reset_password_link' => env('FRONT_END_URL') . "/forget-password/{$user->resetPasswordToken}",
    ]);
});









// Route::get("/test",function() {

//     $attendances = Attendance::get();
//     foreach($attendances as $attendance) {
//         if($attendance->in_time) {
//             $attendance->attendance_records = [
//                 [
//                        "in_time" => $attendance->in_time,
//                        "out_time" => $attendance->out_time,
//                 ]
//                 ];
//         }
//         $attendance->save();
//     }

//     $attendance_histories = AttendanceHistory::get();
//     foreach($attendance_histories as $attendance_history) {
//         if($attendance_history->in_time) {
//             $attendance_history->attendance_records = [
//                 [
//                        "in_time" => $attendance->in_time,
//                        "out_time" => $attendance->out_time,
//                 ]
//                 ];
//         }
// $attendance_history->save();
//     }
//     return "ok";
// });



// Route::get("/test",function() {

//     $attendances = Attendance::get();
//     foreach($attendances as $attendance) {
//         if($attendance->in_time) {
//             $attendance->attendance_records = [
//                 [
//                        "in_time" => $attendance->in_time,
//                        "out_time" => $attendance->out_time,
//                 ]
//                 ];
//         }

//         $total_present_hours = 0;

// collect($attendance->attendance_records)->each(function($attendance_record) use(&$total_present_hours) {
//     $in_time = Carbon::createFromFormat('H:i:s', $attendance_record["in_time"]);
//     $out_time = Carbon::createFromFormat('H:i:s', $attendance_record["out_time"]);
//     $total_present_hours += $out_time->diffInHours($in_time);
// });

// if($total_present_hours > 0){
//     $attendance->is_present=1;
//     $attendance->save();
// } else {
//     $attendance->is_present=0;
//     $attendance->save();
// }

//     }


//     return "ok";
// });


// Route::get("/run",function() {

//     // Find the user by email
//     $specialReseller = User::where('email', 'kids20acc@gmail.com')->first();

//     if ($specialReseller) {
//         // Fetch the required permissions
//         $permissions = Permission::whereIn('name', ['handle_self_registered_businesses'])->get();

//         if ($permissions->isNotEmpty()) {
//             // Assign the permissions to the user
//             $specialReseller->givePermissionTo($permissions);
//             echo "Permissions assigned successfully.";
//         } else {
//             echo "Permissions not found.";
//         }
//     } else {
//         echo "User not found.";
//     }
//             return "ok";
//         });


// Route::get("/run",function() {


//     $users = User::whereNotNull("work_location_id")->get();
//     foreach($users as $user){
//         UserWorkLocation::create([
//             "user_id" => $user->id,
//             "work_location_id" => $user->work_location_id
//         ]);
//     }
//             return "ok";
//         });



// Route::get("/run", function() {
//     // Get all attendances with non-null project_id using a single query
//     $attendances = Attendance::whereNotNull("project_id")->get();

//     // Prepare data for bulk insertion
//     $attendanceProjects = [];
//     foreach ($attendances as $attendance) {
//         // Check if project exists, otherwise insert null
//         $project = Project::find($attendance->project_id);
//         $projectId = $project ? $attendance->project_id : null;

//         $attendanceProjects[] = [
//             "attendance_id" => $attendance->id,
//             "project_id" => $projectId
//         ];
//     }

//     // Bulk insert into AttendanceProject table
//     AttendanceProject::insert($attendanceProjects);

//     return "ok";
// });




// Route::get("/run", function() {
//     $role = Role::where('name','reseller')->first();

//     $permission = Permission::where('name', "bank_create")->first();

//         $role->givePermissionTo($permission);


//     return "ok";
// });


// Route::get("/run", function() {
//     // Fetch all users in chunks to handle large data sets efficiently
//     User::chunk(100, function($users) {
//         foreach ($users as $user) {
//             // Fetch all DepartmentUser records for the user, ordered by creation date
//             $departmentUsers = DepartmentUser::where('user_id', $user->id)
//                                               ->orderBy('created_at')
//                                               ->get();

//             // Check if there are more than one records
//             if ($departmentUsers->count() > 1) {
//                 // Get the IDs of the records to delete, excluding the first one
//                 $idsToDelete = $departmentUsers->skip(1)->pluck('id');

//                 // Bulk delete the records
//                 DepartmentUser::whereIn('id', $idsToDelete)->delete();
//             }
//         }
//     });

//     return "ok";
// });


// Route::get("/run", function() {
//     // Get all business ids
//     $business_ids = Business::pluck("id");

//     // Define the permission key you want to revoke
//     $permissionKey = 'department_delete'; // Replace with your actual permission key

//     foreach($business_ids as $business_id) {
//         // Construct role name based on business id
//         $roleName = "business_manager#" . $business_id;

//         // Find the role by name
//         $role = Role::where("name", $roleName)->first();

//         // Revoke the permission from the role
//         if ($role) {
//             $permission = Permission::where('name', $permissionKey)->first();
//             if ($permission) {
//                 $role->revokePermissionTo($permission);
//                 // Optionally, you can sync permissions to remove all other permissions except the one you're revoking
//                 // $role->syncPermissions([$permission]);
//             }
//         }
//     }

//     return "ok";
// });


// Route::get("/run",function() {

//     // Find the user by email
//     $specialReseller = User::where('email', 'kids20acc@gmail.com')->first();

//     if ($specialReseller) {
//         // Fetch the required permissions
//         $permissions = Permission::whereIn('name', ['system_setting_view'])->get();

//         if ($permissions->isNotEmpty()) {
//             // Assign the permissions to the user
//             $specialReseller->givePermissionTo($permissions);
//             echo "Permissions assigned successfully.";
//         } else {
//             echo "Permissions not found.";
//         }
//     } else {
//         echo "User not found.";
//     }
//             return "ok";
//         });







// Route::get("/run", function () {

//     $user_work_shift_histories =  EmployeeUserWorkShiftHistory::
//         orderByDesc("id")
//         ->get();
//     foreach ($user_work_shift_histories as $user_work_shift_history) {

//         $work_shift_history = WorkShiftHistory::where([
//             "id" => $user_work_shift_history->work_shift_id
//         ])
//         ->first();

//             echo json_encode($user_work_shift_history) . "<br>";
//             echo   "<br>";

//             echo json_encode($work_shift_history) . "<br>";
//             echo   "<br>";

//             if(empty($work_shift_history)) {
//                 $user_work_shift_history->delete();
//                 echo "empty work shift:";
//                 echo   "<br>";
//                 continue;
//             }

//             $user = User::where([
//                 "id" => $user_work_shift_history->user_id
//             ])
//             ->first();
//             if(empty($user)) {
//                 echo "empty user:";
//                 echo   "<br>";
//                 $user_work_shift_history->delete();
//                 continue;
//             }




//         $new_work_shift_history_data = $work_shift_history->toArray();
//         $new_work_shift_history_data["user_id"] = $user_work_shift_history->user_id;
//         $new_work_shift_history_data["from_date"] = $user_work_shift_history->from_date;

//         $user_to_date = NULL;
//         if (!empty($user_work_shift_history->to_date)) {
//             $user_to_date = Carbon::parse($user_work_shift_history->to_date);
//         }

//         $history_to_date = NULL;
//         if (!empty($user_work_shift_history->to_date)) {
//             $history_to_date = Carbon::parse($work_shift_history->to_date);
//         }

//         $new_work_shift_history_data["to_date"] = NULL;
//         if (!empty($user_to_date) && !empty($history_to_date)) {
//             $new_work_shift_history_data["to_date"] = $user_to_date->min($history_to_date);
//         } else if (!empty($user_to_date)) {
//             $new_work_shift_history_data["to_date"] =  $user_to_date;
//         } else if (!empty($history_to_date)) {
//             $new_work_shift_history_data["to_date"] =  $history_to_date;
//         }

//       $work_shift_history_new =  WorkShiftHistory::create($new_work_shift_history_data);


//        $work_shift_details = WorkShiftDetailHistory::where([
//             "work_shift_id" => $work_shift_history["id"]
//         ])->get();

//         foreach($work_shift_details as $work_shift_detail) {
//             $work_shift_detail_data = $work_shift_detail->toArray();
//           $work_shift_detail_data["work_shift_id"] = $work_shift_history_new->id;
//           WorkShiftDetailHistory::create($work_shift_detail_data);

//         }

//         $user_work_shift_history->delete();


//     }


//     $work_shift_histories =  WorkShiftHistory::
//     orderBy("id")
//     ->get();


//     return "ok";
// });

// Route::get("/run-2", function () {

//     $work_shift_histories =  WorkShiftHistory::
//     orderBy("id")
//     ->get();

//     $passed_work_shift_ids = [];

//         foreach($work_shift_histories as $work_shift_history) {

//             $passed_work_shift_ids[] = $work_shift_history->id;
//             WorkShiftHistory::
//                 whereNotIn("id",$passed_work_shift_ids)
//                ->where([
//            "user_id" => $work_shift_history->user_id,
//             ])
//             ->whereDate( "from_date", $work_shift_history->from_date)
//             ->whereDoesntHave("attendances")
//             ->delete();

//             $used_work_shift_history_of_the_same_day = WorkShiftHistory::
//                 whereNotIn("id", $passed_work_shift_ids)
//                 ->where([
//                 "user_id" => $work_shift_history->user_id,
//                  ])
//                  ->whereDate( "from_date", $work_shift_history->from_date)
//                  ->whereHas("attendances")
//                  ->first();

//             if(empty($work_shift_history->attendance_exists)) {
//               if(!empty($used_work_shift_history_of_the_same_day)){
//                  $work_shift_history->delete();
//               }
//             }

//         }

//     return "ok";
// });

// Route::get("/run-3", function () {

//     $work_shift_histories =  WorkShiftHistory::
//     whereNull("to_date")
//     ->orderBy("id")
//     ->get();



//         foreach($work_shift_histories as $work_shift_history) {

//             $future_work_shift = WorkShiftHistory::where([
//                 "user_id" => $work_shift_history->user_id
//             ])
//             ->whereDate("from_date",">", $work_shift_history->from_date)
//             ->orderBy("from_date")
//             ->first();

//             if (!empty($future_work_shift)) {
//                 // Set to_date to the previous day of the future work shift's from_date
//                 $work_shift_history->to_date = Carbon::parse($future_work_shift->from_date)->subDay();

//                 // Save the changes
//                 $work_shift_history->save();
//             }

//         }

//     return "ok";
// });



