<?php

namespace App\Http\Utils;

use App\Models\Announcement;
use App\Models\Business;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EmploymentStatus;
use App\Models\User;
use App\Models\UserAnnouncement;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;

trait BasicUtil
{

    public function dateRanges() {

         $week_dates = $this->getWeekDates();
            $start_date_of_this_week = Carbon::parse($week_dates["start_date_of_this_week"]);
            $end_date_of_this_week = Carbon::parse($week_dates["end_date_of_this_week"]);

    // Calculate previous week start and end based on business_start_day
    $previous_week_start = $start_date_of_this_week->copy()->subWeek();
    $previous_week_end = $previous_week_start->copy()->addDays(6);

         return [
    'today' => today(),
    'start_date_of_this_month' => Carbon::now()->startOfMonth(),
    'end_date_of_this_month' => Carbon::now()->endOfMonth(),
    'start_date_of_previous_month' => Carbon::now()->startOfMonth()->subMonth(),
    'end_date_of_previous_month' => Carbon::now()->startOfMonth()->subDay(),

    'start_date_of_this_week' => $start_date_of_this_week,
    'end_date_of_this_week' => $end_date_of_this_week,
    'start_date_of_previous_week' => $previous_week_start,
    'end_date_of_previous_week' => $previous_week_end,
];


    }

    public function manipulateCurrentData($model_name, $issue_date_column, $expiry_date_column, $user_id)
    {

        $current_data = $this->resolveClassName($model_name)::where("user_id", $user_id)
            ->where($issue_date_column, '<', now())
            ->orderBy(
                DB::raw("IF({$expiry_date_column} IS NOT NULL, {$expiry_date_column}, {$issue_date_column})"),
                'desc'
            )
            ->first();

        if (!empty($current_data)) {
            $current_data->is_current = 1;
            $current_data->save();
        }

        $this->resolveClassName($model_name)::where("user_id", $user_id)
            ->when(!empty($current_data), function ($query) use ($current_data) {
                $query->whereNotIn("id", [$current_data->id]);
            })
            ->update(["is_current" => 0]);
    }

    public function touchUserUpdatedAt($userIds)
    {
        if (!empty($userIds)) {
            User::whereIn('id', $userIds)
                ->update(['updated_at' => now()]);
        }
    }


    function getLast12MonthsDates($year)
    {
        if (empty($year)) {
            $year = Carbon::now()->year;
        }


        $dates = [];


        for ($month = 1; $month <= 12; $month++) {
            // Create a date object for the first day of the current month
            $date = Carbon::createFromDate($year, $month, 1);

            $startOfMonth = $date->copy()->startOfMonth()->toDateString();
            $endOfMonth = $date->copy()->endOfMonth()->toDateString();
            $monthName = $date->copy()->format('F');

            $dates[] = [
                'month' => substr($monthName, 0, 3),
                'start_date' => $startOfMonth,
                'end_date' => $endOfMonth,
            ];
        }

        return $dates;
    }
    public function addAnnouncementIfMissing($all_parent_departments_of_user)
    {

        $announcements_to_show = Announcement::where(function ($query) use ($all_parent_departments_of_user) {
            $query->whereHas("departments", function ($query) use ($all_parent_departments_of_user) {
                $query->whereIn("departments.id", $all_parent_departments_of_user);
            })

                ->orWhereDoesntHave("departments");
        })
            ->pluck("id");

        foreach ($announcements_to_show as $announcement_id) {
            $userAnnouncement =  UserAnnouncement::where([
                "announcement_id" => $announcement_id,
                "user_id" => auth()->user()->id
            ])
                ->first();

            if (empty($userAnnouncement)) {
                UserAnnouncement::create([
                    "announcement_id" => $announcement_id,
                    "user_id" => auth()->user()->id,
                    "status" => "unread"
                ]);
            }
        }
    }



    public function getMainRoleId($user = NULL)
    {
        // Retrieve the authenticated user
        if (empty($user)) {
            $user = auth()->user();
        }


        // Get all roles of the authenticated user
        $roles = $user->roles;

        // Extract the role IDs
        $roleIds = $roles->pluck('id');

        // Find the minimum role ID
        $minRoleId = $roleIds->min();

        return $minRoleId;
    }

    // Define a helper function to resolve class name dynamically
    public function resolveClassName($className)
    {
        return "App\\Models\\" . $className; // Assuming your models are stored in the "App\Models" namespace
    }

    // this function do all the task and returns transaction id or -1

    public function fieldsHaveChanged($fields_to_check, $entity1, $entity2, $date_fields = [])
    {
        foreach ($fields_to_check as $field) {
            $value1 = $entity1[$field];
            $value2 = $entity2[$field];

            // Additional formatting if needed
            if (in_array($field, $date_fields)) {
                $value1 = (new Carbon($value1))->format('Y-m-d');
                $value2 = (new Carbon($value2))->format('Y-m-d');
            }

            if ($value1 !== $value2) {
                return true;
            }
        }
        return false;
    }


    public function getChangedFields($fields_to_check, $entity1, $entity2, $date_fields)
    {
        $changedFields = [];

        foreach ($fields_to_check as $field) {
            $value1 = $entity1->$field;
            $value2 = $entity2[$field];

            // Handle date fields
            if (in_array($field, $date_fields)) {
                $value1 = (new Carbon($value1))->format('Y-m-d');
                $value2 = (new Carbon($value2))->format('Y-m-d');
            }

            // Handle array fields
            if (is_array($value1) && is_array($value2)) {
                $jsonValue1 = json_encode($value1);
                $jsonValue2 = json_encode($value2);

                if ($jsonValue1 !== $jsonValue2) {
                    $changedFields[] = $field;
                }
            } else {
                if ($value1 !== $value2) {
                    $changedFields[] = $field;
                }
            }
        }

        return $changedFields;
    }


    public function getCurrentHistory(string $modelClass, $session_name, $current_user_id, $issue_date_column, $expiry_date_column)
    {

        $model = new $modelClass;

        $user = User::where([
            "id" => $current_user_id
        ])
            ->first();

        if (!$user) {
            return NULL;
        }

        $current_data = NULL;

        $latest_expired_record = $model::where('user_id', $current_user_id)
            ->where($issue_date_column, '<', now())
            ->orderBy($expiry_date_column, 'DESC')
            ->first();


        if ($latest_expired_record) {
            $current_data = $model::where('user_id', $current_user_id)
                ->where($issue_date_column, '<', now())
                ->where($expiry_date_column, $latest_expired_record[$expiry_date_column])
                // ->orderByDesc($issue_date_column)
                ->orderByDesc("id")
                ->first();
        }


        Session::put($session_name, $current_data ? $current_data->id : NULL);
        return $current_data;
    }



    public function get_all_departments_of_manager()
    {
        $auth_user = auth()->user();
        if (empty($auth_user)) {
            return [];
        }
        $all_manager_department_ids = [];
        $manager_departments = Department::where("manager_id", auth()->user()->id)->get();
        foreach ($manager_departments as $manager_department) {
            $all_manager_department_ids[] = $manager_department->id;
            $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
        }
        return $all_manager_department_ids;
    }





    public function get_all_user_of_manager($all_manager_department_ids)
    {
        $all_manager_user_ids = User::whereHas("departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
        ->whereNotIn("users.id",[auth()->user()->id])
            ->pluck("users.id");

        return $all_manager_user_ids->toArray();
    }






    public function all_parent_departments_manager_of_user($user_id, $business_id)
    {
        $all_parent_department_manager_ids = [];
        $assigned_departments = Department::whereHas("users", function ($query) use ($user_id) {
            $query->where("users.id", $user_id);
        })->limit(1)->get();


        foreach ($assigned_departments as $assigned_department) {
            array_push($all_parent_department_manager_ids, $assigned_department->manager_id);
            $all_parent_department_manager_ids = array_merge($all_parent_department_manager_ids, $assigned_department->getAllParentDepartmentManagerIds($business_id));
        }

        // Remove null values and then remove duplicates
        $all_parent_department_manager_ids = array_unique(array_filter($all_parent_department_manager_ids, function ($value) {
            return !is_null($value);
        }));

        return $all_parent_department_manager_ids;
    }


    public function validateUserQuery($user_id, $all_manager_department_ids)
    {

        $user = User::where([
            "id" => $user_id
        ])
            ->where(function ($query) use ($all_manager_department_ids) {
                $query->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                    ->orWhere([
                        "id" => auth()->user()->id
                    ]);
            })
            ->first();

        if (empty($user)) {
            throw new Exception("You don't have access to this user.", 401);
        }
        return $user;
    }

public function generateResponseMessageForBulkDelete($users,  $entity)
{
    // Build array of full names
    $names = [];
    foreach ($users as $user) {
        // Assuming $user is an associative array with keys: title, first_name, middle_name, last_name
        $full_name = trim(($user['title'] ?? '') . ' ' . ($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        if ($full_name !== '') {
            $names[] = $full_name;
        }
    }

    $count = count($names);
    if ($count === 1) {
        $message = ucfirst($entity) . " successfully deleted for {$names[0]}.";
    } elseif ($count > 1) {
        $message = ucfirst($entity) . "s successfully deleted for the following " . implode(', ', $names) . ".";
    } else {
        $message = ucfirst($entity) . "s successfully deleted.";
    }

    return $message;
}

   public function generateResponseMessage($data, $employee, $entity, $type)
{
    $data = json_decode(json_encode($data), true);

    $full_name = trim("{$employee['title']} {$employee['first_Name']} {$employee['middle_Name']} {$employee['last_Name']}");

    switch ($type) {
        case 'create':
            $data['message'] = "The {$entity} has been successfully created for {$full_name}.";
            break;

        case 'update':
            $data['message'] = "The {$entity} has been successfully updated for {$full_name}.";
            break;

        default:
            $data['message'] = "An action was performed on the {$entity} for {$full_name}.";
            break;
    }

    return $data;
}

    public function retrieveData($query, $orderByField, $tableName)
    {

        if(request()->filled("order_by_field") ) {
          $orderByField = request()->input("order_by_field");
        }

        $data =  $query->when(!empty(request()->order_by) && in_array(strtoupper(request()->order_by), ['ASC', 'DESC']), function ($query) use ($orderByField, $tableName) {
            return $query->orderBy($tableName . "." . $orderByField, request()->order_by);
        }, function ($query) use ($orderByField, $tableName) {
            return $query->orderBy($tableName . "." . $orderByField, "DESC");
        })
            ->when(request()->filled("id"), function ($query) use ($tableName) {
                return $query->where($tableName . "." . 'id', request()->input("id"))->first();
            }, function ($query) {
                return $query->when(!empty(request()->per_page), function ($query) {
                    return $query->paginate(request()->per_page);
                }, function ($query) {
                    return $query->get();
                });
            });

        if (request()->filled("id") && empty($data)) {
            throw new Exception("No data found", 404);
        }
        return $data;
    }

    // Function to split, trim, and join each part of the header with "_"
    public function split_trim_join($string)
    {
        return implode("_", array_map('trim', explode(" ", $string)));
    }



    public function generateUniqueId(string $relationModel, int $relationModelId, string $mainModel, string $uniqueIdentifierColumn = 'unique_identifier'): string
    {
        // Fetch the related model instance
        $relation = $relationModel::find($relationModelId);

        // Generate the prefix based on the related model or the authenticated user's business
        $prefix = $this->getPrefix($relation);

        $currentNumber = 1;

        // Generate a unique identifier by checking for existing records
        do {
            $uniqueIdentifier = $prefix . '-' . str_pad($currentNumber, 4, '0', STR_PAD_LEFT);
            $currentNumber++;
        } while ($this->identifierExists($mainModel, $uniqueIdentifierColumn, $uniqueIdentifier));

        return $uniqueIdentifier;
    }


    protected function getPrefix(?Model $relation): string
    {
        // First, check if the authenticated user's business has an identifier prefix
        $businessPrefix = optional(auth()->user()->business)->identifier_prefix;

        if ($businessPrefix) {
            return $businessPrefix;
        }

        // If no business identifier prefix is found, generate a prefix based on the relation's name
        if ($relation) {
            preg_match_all('/\b\w/', $relation->name, $matches);
            $initials = array_map(fn($match) => strtoupper($match[0]), $matches[0]);

            // Limit to the first two initials of each word, or as needed
            return substr(implode('', $initials), 0, 2 * count($matches[0]));
        }

        // If both are not found, return an empty string
        return '';
    }


    protected function identifierExists(string $modelClass, string $column, string $value): bool
    {
        return $modelClass::where($column, $value)
            ->where('business_id', auth()->user()->business_id)
            ->exists();
    }






    public function moveUploadedFiles($files, $location)
    {
        $temporary_files_location = config("setup-config.temporary_files_location");

        foreach ($files as $temp_file_path) {
            $full_temp_path = public_path($temp_file_path);
            $new_location_path = public_path(str_replace($temporary_files_location, $location, $temp_file_path));

            if (File::exists($full_temp_path)) {
                try {
                    // Ensure the destination directory exists
                    $new_directory_path = dirname($new_location_path);
                    if (!File::exists($new_directory_path)) {
                        File::makeDirectory($new_directory_path, 0755, true);
                    }

                    // Attempt to move the file from the temporary location to the permanent location
                    File::move($full_temp_path, $new_location_path);
                    Log::info("File moved successfully from {$full_temp_path} to {$new_location_path}");
                } catch (\Exception $e) {
                    // Log any exceptions that occur during the file move
                    Log::error("Failed to move file from {$full_temp_path} to {$new_location_path}: " . $e->getMessage());
                }
            } else {
                // Log the error if the file does not exist
                Log::error("File does not exist: {$full_temp_path}");
            }
        }
    }




    public function storeUploadedFiles($filePaths, $fileKey, $location, $arrayOfString = NULL, $businessId = NULL)
    {


        if (empty($businessId)) {
            // Get the business ID from the authenticated user
            $businessId = auth()->user()->business_id;
        }


        // Add the business ID to the location path
        $location = "{$businessId}/{$location}";

        if (is_array($arrayOfString)) {
            return collect($filePaths)->map(function ($filePathItem) use ($fileKey, $location) {
                $filePathItem[$fileKey] = $this->storeUploadedFiles($filePathItem[$fileKey], "", $location);
                return $filePathItem;
            });
        }


        // Get the temporary files location from the configuration
        $temporaryFilesLocation = config("setup-config.temporary_files_location");



        // Iterate over each file path in the array and perform necessary operations
        return collect($filePaths)->map(function ($filePathItem) use ($temporaryFilesLocation, $fileKey, $location) {



            $file = !empty($fileKey) ? $filePathItem[$fileKey] : $filePathItem;


            // Construct the full temporary file path and the new location path
            $fullTemporaryPath = public_path($file);

            $newLocation = str_replace($temporaryFilesLocation, $location, $file);
            $newLocationPath = public_path($newLocation);

            // Check if the file exists at the temporary location
            if (File::exists($fullTemporaryPath)) {
                try {
                    // Ensure the destination directory exists
                    $newDirectoryPath = dirname($newLocationPath);
                    if (!File::exists($newDirectoryPath)) {
                        File::makeDirectory($newDirectoryPath, 0755, true);
                    }

                    // Attempt to move the file from the temporary location to the permanent location
                    File::move($fullTemporaryPath, $newLocationPath);
                    Log::info("File moved successfully from {$fullTemporaryPath} to {$newLocationPath}");
                } catch (Exception $e) {
                    throw new Exception(("Failed to move file from {$fullTemporaryPath} to {$newLocationPath}: " . $e->getMessage()), 500);
                }
            }

            // else {
            //     // Log an error if the file does not exist
            //     Log::error("File does not exist: {$fullTemporaryPath}");
            //     throw new Exception("File does not exist",500);
            // }

            // Update the file path in the item if a file key is provided
            if (!empty($fileKey)) {
                $filePathItem[$fileKey] = $newLocation;
            } else {
                // Otherwise, update the item with the new location
                $filePathItem = $newLocation;
            }

            return $filePathItem;
        })->toArray();
    }


    public function makeFilePermanent($filePaths, $fileKey, $arrayOfString = NULL)
    {

        // if(is_array($arrayOfString)) {
        //     return collect($filePaths)->map(function($filePathItem) use ($fileKey) {
        //         $filePathItem[$fileKey] = $this->makeFilePermanent($filePathItem[$fileKey], "");
        //         return $filePathItem;
        //     });

        // }

        // return collect($filePaths)->map(function($filePathItem) use ( $fileKey) {

        //     $file = !empty($fileKey)?$filePathItem[$fileKey]:$filePathItem;
        //     UploadedFile::where([
        //         "file_name" => $file
        //     ])->delete();
        //     return $filePathItem;
        // })->toArray();



    }


    public function moveUploadedFilesBack($filePaths, $fileKey, $location, $arrayOfString = NULL)
    {


        if (is_array($arrayOfString)) {
            return collect($filePaths)->map(function ($filePathItem) use ($fileKey, $location) {
                $filePathItem[$fileKey] = $this->storeUploadedFiles($filePathItem[$fileKey], "", $location);
                return $filePathItem;
            });
        }


        // Get the temporary files location from the configuration
        $temporaryFilesLocation = config("setup-config.temporary_files_location");

        // Iterate over each file path in the array and perform necessary operations
        collect($filePaths)->each(function ($filePathItem) use ($temporaryFilesLocation, $fileKey, $location) {
            // Determine the file path based on whether a file key is provided
            $file = (!empty($fileKey)) ? $filePathItem[$fileKey] : $filePathItem;

            // Construct the full destination path and the temporary location path
            $destinationPath = public_path($file);
            $temporaryLocation = str_replace($location, $temporaryFilesLocation, $file);

            // Check if the file exists at the current location
            if (File::exists($destinationPath)) {
                try {
                    // Ensure the temporary directory exists
                    $temporaryDirectoryPath = dirname($temporaryLocation);
                    if (!File::exists($temporaryDirectoryPath)) {
                        File::makeDirectory($temporaryDirectoryPath, 0755, true);
                    }

                    // Attempt to move the file back to the temporary location
                    File::move($destinationPath, public_path($temporaryLocation));
                    Log::info("File moved back successfully from {$destinationPath} to {$temporaryLocation}");
                } catch (\Exception $e) {
                    // Log any exceptions that occur during the file move back
                    Log::error("Failed to move file back from {$destinationPath} to {$temporaryLocation}: " . $e->getMessage());
                }
            } else {
                // Log an error if the file does not exist at the current location
                Log::error("File does not exist at destination: {$destinationPath}");
            }
        });
    }


    public function get_all_parent_department_manager_ids($all_parent_department_ids)
    {
        $manager_ids = Department::whereIn("id", $all_parent_department_ids)->pluck("manager_id")
            ->filter() // Removes null values
            ->unique()
            ->values();
        return $manager_ids;
    }


    public function checkJoinAndTerminationDate($user_joining_date, $date, $termination, $throwError = false)
    {

        $joining_date = Carbon::parse($user_joining_date);
        $in_date = Carbon::parse($date);

        if (!empty($termination)) {

            $last_termination_date = Carbon::parse($termination->date_of_termination);
            $last_joining_date = Carbon::parse($termination->joining_date);

            // Check if the provided date is between the last joining and termination date
            if ($in_date->gte($last_joining_date) && $in_date->lte($last_termination_date)) {
                return [
                    "failure_message" => "",
                    "success" => true
                ];
            }
        }

        if ($joining_date->gt($in_date)) {
            $failureMessage = sprintf(
                'Invalid attendance date: Employee joined on %s, but the provided date is %s.',
                $joining_date->format('d/m/Y'),
                $in_date->format('d/m/Y')
            );

            if ($throwError) {
                throw new Exception($failureMessage, 403);
            }

            return [
                "failure_message" => $failureMessage,
                "success" => false
            ];
        }



        return [
            "failure_message" => "",
            "success" => true
        ];
    }


    public function manipulateJoiningDateTerminationDate($joining_date, $date_of_termination, $start_date, $end_date)
    {
        try {
            $joining_date = Carbon::parse($joining_date);
            $start_date = Carbon::parse($start_date);
            $end_date = Carbon::parse($end_date);

            if ($date_of_termination) {
                $date_of_termination = Carbon::parse($date_of_termination);
            }
        } catch (Exception $e) {
            // Return default dates if parsing fails
            return [
                "start_date" => '1970-01-01',
                "end_date"   => '1970-01-01',
                "message"   => 'A1',
            ];
        }

        if ($joining_date->gt($end_date)) {
            return [
                "start_date" => '1970-01-01',
                "end_date"   => '1970-01-01',
                "message"   => 'A2-joining_date:'.$joining_date."-end_date:".$end_date,

            ];
        }

        if ($joining_date->gt($start_date)) {
            $start_date = $joining_date;
        }

        if ($date_of_termination) {
            if ($date_of_termination->lt($start_date)) {
                return [
                    "start_date" => '1970-01-01',
                    "end_date"   => '1970-01-01',
                    "message"   => 'A3',
                ];
            }
            if ($date_of_termination->lt($end_date)) {
                $end_date = $date_of_termination;
            }
        }



        return [
            "joining_date" => $joining_date,
            "start_date" => $start_date->toDateString(),
            "end_date"   => $end_date->toDateString(),
            "message" => "A4"
        ];
    }

    public function getWeekDates()
    {
        $authUser = auth()->user();
        $business_start_day = $authUser->business?->business_start_day ?? 1; // 1 = Monday by default
        $currentDate = Carbon::now();
        $start_date_of_this_week = $currentDate->copy()->startOfDay();

        if ($start_date_of_this_week->dayOfWeek !== $business_start_day % 7) {
            $start_date_of_this_week = $start_date_of_this_week->previous($business_start_day % 7);
        }
        $end_date_of_this_week = $start_date_of_this_week->copy()->addDays(6);

        return [
            "start_date_of_this_week" => $start_date_of_this_week,
            "end_date_of_this_week" => $end_date_of_this_week
        ];
    }


    public function getDefaultDepartment()
    {
        $department = Department::where(
            [
                "is_active" => 1,
                "business_id" => auth()->user()->business_id,
                "manager_id" => auth()->user()->id
            ]
        )

            ->first();
        if (empty($department)) {

            $department = Department::where(
                [
                    "is_active" => 1,
                    "business_id" => auth()->user()->business_id,
                ]
            )
                ->whereNull("parent_id")
                ->first();

            if (empty($department)) {
                throw new Exception("No default department found for the business", 500);
            }
        }

        return $department;
    }

    public function getDefaultWorkLocation($business)
    {
        $work_location = WorkLocation::where([
            "is_active" => 1,
            "is_default" => 1,
            "business_id" => auth()->user()->business_id,
        ])
            ->first();

        if (empty($work_location)) {
            // throw new Exception("No default work location found for the business",500);
            $work_location =  WorkLocation::create([
                'name' => ($business->name . " " . "Office"),
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => $business->id,
                "created_by" => $business->owner_id
            ]);
        }
        return $work_location;
    }

    public function getDefaultDesignation()
    {

        $designation_name = "Office Worker";

        $designation = Designation::where([
            "name" => $designation_name,
            "is_active" => 1,
            "is_default" => 1,
            "business_id" => NULL,
            "created_by" => 1
        ])
            ->first();

        if (empty($designation)) {
            $designation =  Designation::create([
                "name" => $designation_name,
                "description" => $designation_name,
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => NULL,
                "created_by" => 1
            ]);
        }
        return $designation;
    }


    public function getDefaultEmploymentStatus()
    {

        $employment_status_name = "Full-Time";
        $employment_status_description =  "Employee works the standard number of hours for a full-time position.";

        $employment_status = EmploymentStatus::where([
            "name" => $employment_status_name,
            "is_active" => 1,
            "is_default" => 1,
            "business_id" => NULL,
            "created_by" => 1
        ])
            ->first();

        if (empty($employment_status)) {
            $employment_status =  EmploymentStatus::create([
                "name" => $employment_status_name,
                "description" => $employment_status_description,
                "is_active" => 1,
                "is_default" => 1,
                "business_id" => NULL,
                "created_by" => 1
            ]);
        }
        return $employment_status;
    }


    public function getLetterTemplateVariablesFunc()
    {
        $letterTemplateVariables = [
            'PERSONAL DETAILS',
            '[FULL_NAME]',
            '[NI_NUMBER]',
            '[DATE_OF_BIRTH]',
            '[GENDER]',
            '[PHONE]',
            '[EMAIL]',
            'EMPLOYMENT DETAILS',
            '[DESIGNATION]',
            '[EMPLOYMENT_STATUS]',
            '[JOINING_DATE]',
            '[SALARY_PER_ANNUM]',
            '[WEEKLY_CONTRACTUAL_HOURS]',
            '[MINIMUM_WORKING_DAYS_PER_WEEK]',
            '[OVERTIME_RATE]',
            'ADDRESS',
            '[ADDRESS_LINE_1]',
            // '[ADDRESS_LINE_2]',
            '[CITY]',
            '[POSTCODE]',
            '[COUNTRY]',
            'BANK DETAILS',
            '[SORT_CODE]',
            '[ACCOUNT_NUMBER]',
            '[ACCOUNT_NAME]',
            '[BANK_NAME]',

            'COMPANY DETAILS',
            'COMPANY_NAME',
            'COMPANY_ADDRESS_LINE_1',
            'COMPANY_CITY',
            'COMPANY_POSTCODE',
            'COMPANY_COUNTRY',


            'TERMINATION DETAILS',
            'TERMINATION_DATE',
            'REASON_FOR_TERMINATION',
            'TERMINATION_TYPE',



            'TYPE_OF_LEAVE',
            'LEAVE_START_DATE',
            'LEAVE_END_DATE',
            'TOTAL_DAYS'



        ];







        return $letterTemplateVariables;
    }



    function toggleActivation($modelClass, $disabledModelClass, $modelIdName, $modelId, $authUser)
    {
        // Fetch the model instance
        $modelInstance = $modelClass::where('id', $modelId)->first();
        if (!$modelInstance) {
            return response()->json([
                "message" => "No data found"
            ], 404);
        }

        $shouldUpdate = 0;
        $shouldDisable = 0;

        // Handle role-based permission
        if (empty($authUser->business_id)) {
            if ($authUser->hasRole('superadmin')) {
                if ($modelInstance->business_id !== NULL) {
                    return response()->json([
                        "message" => "You do not have permission to update this item due to role restrictions."
                    ], 403);
                } else {
                    $shouldUpdate = 1;
                }
            } else {
                if ($modelInstance->business_id !== NULL) {
                    return response()->json([
                        "message" => "You do not have permission to update this item due to role restrictions."
                    ], 403);
                } else if ($modelInstance->is_default == 0) {
                    if ($modelInstance->created_by != $authUser->id) {
                        return response()->json([
                            "message" => "You do not have permission to update this item due to role restrictions."
                        ], 403);
                    } else {
                        $shouldUpdate = 1;
                    }
                } else {
                    $shouldUpdate = 1;
                    // $shouldDisable = 1;
                }
            }
        } else {
            if ($modelInstance->business_id !== NULL) {
                if ($modelInstance->business_id != $authUser->business_id) {
                    return response()->json([
                        "message" => "You do not have permission to update this item due to role restrictions."
                    ], 403);
                } else {
                    $shouldUpdate = 1;
                }
            } else {
                if ($modelInstance->is_default == 0) {
                    if ($modelInstance->created_by != $authUser->id) {
                        return response()->json([
                            "message" => "You do not have permission to update this item due to role restrictions."
                        ], 403);
                    } else {
                        $shouldDisable = 1;
                    }
                } else {
                    $shouldDisable = 1;
                }
            }
        }

        // Perform the update action
        if ($shouldUpdate) {
            $modelInstance->update([
                'is_active' => !$modelInstance->is_active
            ]);
        }

        // Handle disabling the model
        if ($shouldDisable) {
            $disabledInstance = $disabledModelClass::where([
                $modelIdName => $modelInstance->id,
                'business_id' => $authUser->business_id,
                'created_by' => $authUser->id,
            ])->first();

            if (!$disabledInstance) {
                $disabledModelClass::create([
                    $modelIdName => $modelInstance->id,
                    'business_id' => $authUser->business_id,
                    'created_by' => $authUser->id,
                ]);
            } else {
                $disabledInstance->delete();
            }
        }
    }





    public function logInClient()
    {


        // Step 1: Check required identity fields
        $required_fields = ['first_Name', 'last_Name', 'date_of_birth', 'employee_id', "business_id"];
        foreach ($required_fields as $field) {
            if (!request()->filled($field)) {
                throw new Exception("The {$field} field is required.", 400);
            }
        }

        // Step 2: Attempt to find the user
        $user = User::whereRaw('LOWER(first_Name) = ?', [strtolower(request()->first_Name)])
            ->whereRaw('LOWER(last_Name) = ?', [strtolower(request()->last_Name)])
            ->when(request()->filled("middle_Name"), function ($query) {
                $query->whereRaw('LOWER(middle_Name) = ?', [strtolower(request()->middle_Name)]);
            })
            ->whereRaw('LOWER(date_of_birth) = ?', [strtolower(request()->date_of_birth)])
            ->whereRaw('LOWER(user_id) = ?', [strtolower(request()->employee_id)])
            ->where('business_id', request()->input("business_id"))

            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid user information.'
            ], 404);
        }

        // Step 3: Log the user in temporarily
        Auth::login($user);
    }
}
