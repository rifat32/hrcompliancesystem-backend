<?php

namespace App\Http\Components;

use App\Http\Utils\BasicUtil;
use App\Models\Project;
use App\Models\Task;


class ProjectComponent
{
    use BasicUtil;

    public function getProjects()
    {
        $projectsQuery = Project::with(
            [
                "users" => function ($query) {
                    $query->select(
                        'users.id',
                        "users.title",
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                },
                "departments" => function ($query) {
                    $query->select('departments.id', 'departments.name');
                }
            ]

        )

            ->where(
                [
                    "projects.business_id" => auth()->user()->business_id
                ]
            )
            ->when(!empty(request()->user_id), function ($query) {
                return $query->whereHas('users', function ($query) {
                    $query->where("users.id", request()->user_id);
                });
            })
            ->when(!empty(request()->assigned_user_id_not), function ($query) {
                return $query->whereDoesntHave('users', function ($query) {
                    $query->where("users.id", request()->assigned_user_id_not);
                });
            })


            ->when(!empty(request()->search_key), function ($query) {
                return $query->where(function ($query) {
                    $term = request()->search_key;
                    $query->where("projects.name", "like", "%" . $term . "%")
                        ->orWhere("projects.description", "like", "%" . $term . "%");
                });
            })
            ->when(!empty(request()->name), function ($query) {
                return $query->where(function ($query) {
                    $term = request()->name;
                    $query->where("projects.name", "like", "%" . $term . "%");
                });
            })
            ->when(!empty(request()->status), function ($query) {
                return $query->where(function ($query) {
                    $term = request()->status;
                    $query->where("projects.status",  $term);
                });
            })



            ->when(!empty(request()->start_date), function ($query) {
                return $query->where('projects.start_date', ">=", request()->start_date);
            })
            ->when(!empty(request()->end_date), function ($query) {
                return $query->whereDate('projects.end_date', "<=", (request()->end_date));
            })
            ->when(!empty(request()->in_date), function ($query) {
                return $query->where('projects.start_date', "<=", (request()->in_date . ' 00:00:00'))
                    ->whereDate('projects.end_date', "<=", request()->in_date);
            })


            ->select(
                'projects.*',

            )
            ->selectSub(
                Task::selectRaw('COUNT(*)')
                    ->whereColumn('tasks.project_id', 'projects.id'),
                'tasks_count'
            );

             $projects =  $this->retrieveData($projectsQuery, "id", "projects");






        return $projects;
    }
}
