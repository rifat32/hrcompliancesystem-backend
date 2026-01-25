<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Department extends Model
{
    use HasFactory;
    protected $appends = ['total_users_count'];
    protected $fillable = [
        "name",
        "work_location_id",
        "description",
        "is_active",
        "manager_id",
        "parent_id",
        "business_id",
        "created_by"
    ];


    public function job_listings()
    {
        return $this->hasMany(JobListing::class, 'department_id', 'id');
    }




    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id', 'id');
    }


    public function getAllDescendantIds()
    {
        $descendantIds = [];
        $this->getDescendantIdsRecursive($this, $descendantIds);
        return $descendantIds;
    }

    protected function getDescendantIdsRecursive($department, &$descendantIds)
    {
        foreach ($department->children as $child) {
            $descendantIds[] = $child->id;

            // Recursively get the descendants of the current child
            $this->getDescendantIdsRecursive($child, $descendantIds);
        }
    }

    public function getAllParentIds()
    {
        $parentIds = [];
        $this->getParentIdsRecursive(
            $this,
            $parentIds,
            Department::where([
                "business_id" => auth()->user()->business_id
            ])
                ->count()


        );


        return $parentIds;
    }
    public function getAllParentDepartmentManagerIds($business_id)
    {


        $parentDepartmentManagerIds = [];
        $this->getParentDepartmentManagerIdsRecursive(
            $this,
            $parentDepartmentManagerIds,
            Department::where(
                [
                    "business_id" => $business_id
                ]
            )
                ->count()
        );

        return $parentDepartmentManagerIds;
    }


    protected function getParentIdsRecursive($department, &$parentIds, $depth = 0)
    {
        // Check if we've reached the depth limit
        if ($depth >= 30) {
            return;
        }

        if ($department->parent) {
            // Include the parent ID
            $parentIds[] = $department->parent->id;

            // Recursively get the parent IDs of the current parent
            $this->getParentIdsRecursive($department->parent, $parentIds, $depth + 1);
        }
    }

    protected function getParentDepartmentManagerIdsRecursive($department, &$parentDepartmentManagerIds, $depth = 0)
    {



        // Check if we've reached the depth limit
        if ($depth >= 30) {
            return;
        }



        if ($department->parent) {

            // Include the parent ID
            $parentDepartmentManagerIds[] = $department->parent->manager_id;

            // Recursively get the parent IDs of the current parent
            $this->getParentDepartmentManagerIdsRecursive($department->parent, $parentDepartmentManagerIds, $depth + 1);
        }
    }

    public function getAllParentManagerIds()
    {
        $parentManagerIds = [];
        $this->getParentManagerIdsRecursive($this, $parentManagerIds);

        return $parentManagerIds;
    }
    protected function getParentManagerIdsRecursive($department, &$parentManagerIds)
    {
        if ($department->parent) {
            // Include the parent ID
            $parentManagerIds[] = $department->parent->manager_id;

            // Recursively get the parent IDs of the current parent
            $this->getParentManagerIdsRecursive($department->parent, $parentManagerIds);
        }
    }




    public function recursiveChildren()
    {
        return $this->children()->with([
            'recursiveChildren',
            'manager' => function ($query) {
                $query->select(
                    "users.id",
                    'users.title',
                    'users.first_Name',
                    'users.middle_Name',
                    'users.last_Name',
                    'users.image',
                     'users.designation_id',
                )
                    ->with([
                        'designation' => function ($query) {
                            $query->select(
                                "designations.id",
                                'designations.name',
                            );
                        },
                    ]);
            },
            'users' => function ($query) {

                $query
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->whereDate("users.joining_date", "<=", today())
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", today())
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    })
                ;
            },
            "job_listings"
        ]);
    }

    public function recursiveManager()
    {
        return $this->children()->with(
            'manager',
            "users.recursive_manager_departments"
        );
    }



    public function getTotalUsersCountAttribute()
    {

        return $this->users()
            ->whereNotIn('users.id', [auth()->user()->id])
                        ->whereDate("users.joining_date", "<=", today())
                        ->whereDoesntHave("lastTermination", function ($query) {
                            $query->where('terminations.date_of_termination', "<", today())
                                ->whereRaw('terminations.date_of_termination > users.joining_date');
                        })
            ->count();
    }




    public function children_recursive()
    {
        return $this->hasMany(Department::class, 'parent_id', 'id')->with(
            [
                "children_recursive" => function ($query) {
                    $query->select('departments.id', 'departments.name'); // Specify the fields for the creator relationship
                },
                "manager" => function ($query) {
                    $query->select(
                        'users.id',
                        "users.title",
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                }

            ]


        )

            ->addSelect([
                'total_users_count' => DepartmentUser::selectRaw('COUNT(*)')
                    ->whereColumn('departments.id', 'department_id')
            ]);;
    }


    public function payrun_department()
    {
        return $this->hasOne(PayrunDepartment::class, "department_id", 'id');
    }




    public function work_location()
    {
        return $this->belongsTo(WorkLocation::class, "work_location_id", 'id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }




    public function employee_rota()
    {
        return $this->hasOne(EmployeeRota::class, "department_id", 'id');
    }


    public function users()
    {
        return $this->belongsToMany(User::class, 'department_users', 'department_id', 'user_id');
    }



    public function recursive_manager_users()
    {
        return $this->belongsToMany(User::class, 'department_users', 'department_id', 'user_id')->with("recursive_department_users");
    }


    public function announcements()
    {
        return $this->belongsToMany(Announcement::class, 'department_announcements', 'department_id', 'announcement_id');
    }
    public function work_shifts()
    {
        return $this->belongsToMany(WorkShift::class, 'department_work_shifts', 'department_id', 'work_shift_id');
    }
}
