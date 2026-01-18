<!DOCTYPE html>
<html>

<head>
    <title>Attendance List</title>

    <!--ALL CUSTOM FUNCTIONS -->
    @php

        function format_days($count)
        {
            return $count . ' ' . ($count == 1 ? 'day' : 'days');
        }
        // Define a function within the Blade file
        function processString($inputString)
        {
            // Remove underscore
            $withoutUnderscore = str_replace('_', '', $inputString);

            // Remove everything from the pound sign (#) and onwards
            $finalString = explode('#', $withoutUnderscore)[0];

            // Capitalize the string
            $capitalizedString = ucwords($finalString);

            return $capitalizedString;
        }

        function time_format($breakHours)
        {
            if (!$breakHours) {
                $breakHours = 0;
            }

            // Convert break hours to seconds
            $breakSeconds = round($breakHours * 3600);

            // Format seconds to "00:00:00" time format
            $formattedBreakTime = gmdate('H:i:s', $breakSeconds);
            return $formattedBreakTime;
        }
        function format_date($date)
        {
            return \Carbon\Carbon::parse($date)->format('d-m-Y');
        }
        function get_day_from_date($date)
        {
            return \Carbon\Carbon::createFromFormat('d-m-Y', $date)->format('D');
        }

    @endphp

    @php
        $business = auth()->user()->business;
    @endphp

    @php
        $color = env('FRONT_END_VERSION') == 'red' ? '#dc2b28' : '#335ff0';
    @endphp

    <style>
        /* Add any additional styling for your PDF */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .table_head_row {
            color: #fff;
            background-color: {{ $color }};
            font-weight: 600;
        }

        .table_head_row td {
            color: #fff;
        }

        .table_head_row2 {
            color: #fff;
            background-color: rgb(87, 87, 87);
            font-weight: 600;
        }

        .table_head_row2 td {
            color: #fff;
        }

        .table_head_row2 th,
        tbody tr td {
            text-align: left;
            padding: 10px 0px;
        }

        .table_head_row th,
        tbody tr td {
            text-align: left;
            padding: 10px 0px;
        }

        .table_row {
            background-color: #ffffff;
        }

        .table_row td {
            padding: 10px 0px;
            border-bottom: 0.2px solid #ddd;
        }

        .table_row2 {
            background-color: #d6d6d6;
        }

        .table_row2 td {
            padding: 10px 0px;
            border-bottom: 0.2px solid #ddd;
        }

        .employee_index {}

        .employee {
            color: {{ $color }};
            /*font-weight:600;*/
        }

        .employee_details {
            padding: 10px 10px 10px;
        }

        .employee_attendance_summary {
            margin-top: 0;
            margin-bottom: 0;
            margin-left: 18px;
            font-size: 12px;
            padding: 0px;
        }

        .employee_attendance_summary span {
            font-weight: bold
        }

        .employee_name {
            font-weight: bold
        }

        .role {}

        .logo {
            width: 75px;
            height: 75px;
        }

        .file_title {
            font-size: 1.3rem;
            font-weight: bold;
            text-align: right;
        }

        .business_name {
            font-size: 1.2rem;
            font-weight: bold;
            display: block;
        }

        .business_address {}

        .css-logo {
            width: 150px;
            /* Set the width of the logo */
            height: 50px;
            /* Set the height of the logo */
            background-color: {{ $color }};
            /* Set a background color (blue in this case) */
            color: white;
            /* Text color */
            font-size: 15px;
            /* Font size */
            font-weight: bold;
            /* Make the font bold */
            border-radius: 5px;
            /* Optional: Round the corners */
            text-align: center;
            /* Ensure the text is centered */
            text-transform: uppercase;
            /* Optional: Make the text uppercase */
            letter-spacing: 1px;
            /* Optional: Add spacing between letters */

        }
    </style>

</head>

<body>

    {{-- PDF HEADING  --}}
    <table style="margin-top:-30px">
        <tbody>
            <tr>
                @php
                    $logo_path = public_path($business->logo); // Get the full path of the logo
                @endphp

                @if ($business->logo && file_exists($logo_path))
                    <td rowspan="2">
                        <img class="logo" src="{{ asset($business->logo) }}">
                    </td>
                @else
                    <td rowspan="2">
                        <span class="business_name">{{ $business->name }}</span>
                        <address class="business_address">{{ $business->address_line_1 }}</address>
                    </td>
                @endif
                <td></td>
            </tr>
            <tr>
                <td class="file_title">Attendance Summary</td>
            </tr>
            @if ($business->logo && file_exists($logo_path))
                <tr>
                    <td style="background-color: #000;">
                        <span class="business_name">{{ $business->name }}</span>
                        <address class="business_address">{{ $business->address_line_1 }}</address>
                    </td>
                </tr>
            @endif
            <tr>
                <td>
                    <span style="font-weight: bold">Legend:</span>
                    <div>P - Present | A - Absent | H - Holiday | L - Leave</div>
                </td>
            </tr>
 <tr>

            </tr>
            <tr>
                <td>
                    <span style="font-weight: bold">Filters:</span>
                    <ul>
@foreach(request()->all() as $key => $value)
@if ($key == "user_id"&& !empty($value))
 <li><strong>Employee Name</strong>: @foreach ($employees as $index => $employee)
   {{$employee["title"] . " " . $employee["first_Name"] . " " . $employee["middle_Name"] . " " . $employee["last_Name"]}},
 @endforeach</li>

@elseif ($key == "project_id"&& !empty($value))
 <li><strong>Project</strong>: @foreach (\App\Models\Project::whereIn("id",explode(',', $value))->get() as $index => $project)
   {{$project["name"]}},
 @endforeach</li>
 @elseif ($key == "department_id"&& !empty($value))
 <li><strong>Department</strong>: @foreach (\App\Models\Department::whereIn("id",explode(',', $value))->get() as $index => $department)
   {{$department["name"]}},
 @endforeach</li>
  @elseif ($key == "designation_ids"&& !empty($value))
 <li><strong>Designation</strong>: @foreach (\App\Models\Designation::whereIn("id",explode(',', $value))->get() as $index => $designation)
   {{$designation["name"]}},
 @endforeach</li>
  @elseif ($key == "employee_work_shift_id"&& !empty($value))
 <li><strong>WorkShift</strong>: @foreach (\App\Models\WorkShift::whereIn("id",explode(',', $value))->get() as $index => $work_shift)
   {{$work_shift["name"]}},
 @endforeach</li>
 @elseif ($key == "is_leave_taken"&& isset($value))
 <li><strong>leave taken?</strong>: {{ !empty($value)?"Yes":"No" }}</li>

@elseif ($key == "work_availability_percentage" && !empty($value))
    @php
        $ranges = explode(',', $value);
        $from = $ranges[0] ?? '';
        $to = $ranges[1] ?? '';
    @endphp
    <li><strong>Work Availability Percentage</strong>: {{ $from }} to {{ $to }}</li>

 @elseif ($key == "is_late_employee" && isset($value))
 <li><strong>Late Employee?</strong>: {{ !empty($value)?"Yes":"No" }}</li>


 @elseif ($key == "start_date"&& !empty($value))
 <li><strong>Start Date</strong>: {{ format_date($value) }}</li>
 @elseif ($key == "end_date"&& !empty($value))
 <li><strong>End Date</strong>: {{ format_date($value) }}</li>
@else


@endif

@endforeach
</ul>

                </td>
            </tr>
        </tbody>
    </table>


    @if (count($employees))
        @foreach ($employees as $index => $employee)
            <div style="border: 1px solid #aaa;margin-bottom: 10px; border-radius: 5px; overflow: hidden;">
                {{-- EMPLOYEE DETAILS  --}}
                <div class="employee_details">
                    <span class="index_col">{{ $index + 1 }}</span>
                    <span class="employee_name">
                        {{ $employee['title'] .
                            ' ' .
                            $employee['first_Name'] .
                            ' ' .
                            $employee['middle_Name'] .
                            ' ' .
                            $employee['last_Name'] }}
                    </span>
                    <table>
                        <tbody>
                            <tr>
                                <td>
                                    <p class="employee_attendance_summary">
                                        <span>Scheduled:</span>
                                        {{ format_days($employee['data']['data_highlights']['total_schedule_days']) }}
                                    </p>
                                </td>
                                <td>
                                    <p class="employee_attendance_summary">
                                        <span>Present:</span>
                                        {{ format_days($employee['data']['data_highlights']['total_working_days']) }}
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="employee_attendance_summary">
                                        <span>Absent:</span>
                                        {{ format_days($employee['data']['data_highlights']['total_absent_days']) }}
                                    </p>
                                </td>
                                <td>
                                    <p class="employee_attendance_summary">
                                        <span>Leave:</span>
                                        {{ format_days($employee['data']['data_highlights']['total_leave_days']) }}
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <p class="employee_attendance_summary">
                                        <span>Late:</span>
                                        {{ format_days($employee['data']['data_highlights']['total_late_days']) }}
                                    </p>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- EMPLOYEE'S RECORDS  --}}
                <table>
                    {{-- RECORDS HEADING --}}
                    <thead>
                        <tr class="table_head_row">
                            <th style="padding-left: 5px;">Date</th>
                            <th style="text-align: center;">Day</th>
                            <th style="text-align: center;">Scheduled</th>
                            <th style="text-align: center;">Worked</th>
                            <th style="text-align: center;">Break</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: center;">Overtime</th>
                            <th style="padding-left: 5px;padding-right: 5px; text-align: center">Lateness status</th>
                        </tr>
                    </thead>

                    {{-- RECORD BODY --}}
                    <tbody>
                        {{-- IF HAVE ANY RECORDS --}}
                        @if (count($employee['data']['data']))
                            @foreach ($employee['data']['data'] as $attendance_leave_holiday_workshift)
                                <tr class="table_row">
                                    {{-- 1st COL DATE  --}}
                                    @if ($attendance_leave_holiday_workshift->type == 'holiday')
                                        <td style="padding-left:5px;font-weight:bold;color:green">
                                            {{ format_date($attendance_leave_holiday_workshift->in_date) }}
                                        </td>
                                        <td style="font-weight:bold;color:green;text-align:center;">
                                            {{ get_day_from_date(format_date($attendance_leave_holiday_workshift->in_date)) }}
                                        </td>
                                    @elseif ($attendance_leave_holiday_workshift->type == 'leave_record')
                                        <td style="padding-left:5px;font-weight:bold;color:red">
                                            {{ format_date($attendance_leave_holiday_workshift->in_date) }}
                                        </td>
                                        <td style="font-weight:bold;color:red;text-align:center;">
                                            {{ get_day_from_date(format_date($attendance_leave_holiday_workshift->in_date)) }}
                                        </td>
                                    @elseif ($attendance_leave_holiday_workshift->type == 'work_shift_detail')
                                        <td style="padding-left: 5px;">
                                            {{ format_date($attendance_leave_holiday_workshift->in_date) }}
                                        </td>
                                        <td style="text-align:center;">
                                            {{ get_day_from_date(format_date($attendance_leave_holiday_workshift->in_date)) }}
                                        </td>
                                    @elseif ($attendance_leave_holiday_workshift->type == 'attendance')
                                        <td style="padding-left: 5px;">
                                            {{ format_date($attendance_leave_holiday_workshift->in_date) }}
                                        </td>
                                        <td style="text-align:center;">
                                            {{ get_day_from_date(format_date($attendance_leave_holiday_workshift->in_date)) }}
                                        </td>
                                    @endif


                                    {{-- 2nd COL TYPE  --}}
                                    @if ($attendance_leave_holiday_workshift->type == 'holiday')
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td style="text-align:center;font-weight:bold;color:green">
                                            H
                                        </td>
                                    @elseif ($attendance_leave_holiday_workshift->type == 'leave_record')
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td style="text-align:center;font-weight:bold;color:red">L
                                        </td>
                                    @endif

                                    {{-- SCHEDULED  --}}
                                    @if (
                                        $attendance_leave_holiday_workshift->type == 'attendance' &&
                                            $attendance_leave_holiday_workshift->type != 'leave_record')
                                        <td style="text-align: center;">
                                            {{ convertFloatHoursToTime($attendance_leave_holiday_workshift->capacity_hours - $attendance_leave_holiday_workshift->leave_hours) }}
                                        </td>
                                    @elseif (!in_array($attendance_leave_holiday_workshift->type, ['holiday', 'leave_record']))
                                        <td style="text-align: center;">
                                            {{ convertFloatHoursToTime($attendance_leave_holiday_workshift->capacity_hours) }}
                                        </td>
                                    @endif

                                    {{-- WORKED  --}}
                                    <td style="text-align: center;">
                                        @if ($attendance_leave_holiday_workshift->type == 'attendance')
                                            {{ convertFloatHoursToTime($attendance_leave_holiday_workshift['total_paid_hours']) }}
                                        @endif
                                    </td>

                                    {{-- Break  --}}
                                    <td style="text-align: center;">
                                        @if ($attendance_leave_holiday_workshift->type == 'attendance')
                                            @if (convertFloatHoursToTime($attendance_leave_holiday_workshift['paid_break_hours']))
                                                {{ convertFloatHoursToTime($attendance_leave_holiday_workshift['paid_break_hours']) }}
                                                (Paid)
                                            @else
                                                {{ convertFloatHoursToTime($attendance_leave_holiday_workshift['unpaid_break_hours']) }}
                                                (Unpaid)
                                            @endif
                                        @endif
                                    </td>

                                    @if (
                                        $attendance_leave_holiday_workshift->type == 'attendance' ||
                                            $attendance_leave_holiday_workshift->type == 'work_shift_detail')
                                        <td style="text-align: center;">
                                            {{ $attendance_leave_holiday_workshift->is_present ? 'P' : 'A' }}
                                        </td>
                                    @endif

                                    <td style="text-align: center;">
                                        @if ($attendance_leave_holiday_workshift->type == 'attendance')
                                            {{ convertFloatHoursToTime($attendance_leave_holiday_workshift['overtime_hours']) }}
                                        @endif

                                    </td>

                                    @if (
                                        $attendance_leave_holiday_workshift->type == 'attendance' ||
                                            $attendance_leave_holiday_workshift->type == 'work_shift_detail')
                                        <td style="padding-left: 5px;padding-right: 5px; text-align: center;">
                                            @switch($attendance_leave_holiday_workshift->behavior)
                                                @case('regular')
                                                    {{ 'Regular' }}
                                                @break

                                                @case('early')
                                                    {{ 'Early' }}
                                                @break

                                                @case('late')
                                                    {{ 'Late' }}
                                                @break
                                            @endswitch
                                        </td>
                                    @endif
                                </tr>

                                {{-- ATTENDANCE'S RECORDS  --}}
                                @if ($attendance_leave_holiday_workshift->type == 'attendance')
                                    <tr style="border-bottom: 1px solid #aaa">
                                        <td colspan="8" style=" padding: 0px!important;">
                                            <table>
                                                {{-- RECORDS HEADING --}}
                                                <thead>
                                                    <tr class="table_head_row2">
                                                        <th style="padding-left: 5px;">Start At</th>
                                                        <th style="">End At</th>
                                                        <th style="text-align: center;">Total</th>
                                                        <th style="text-align: center;">Break</th>
                                                        <th style="text-align: center;">Projects</th>
                                                        <th style="text-align: center;">Work site</th>
                                                    </tr>
                                                </thead>

                                                {{-- RECORD BODY --}}
                                                <tbody>
                                                    @if (count($attendance_leave_holiday_workshift['attendance_records']))
                                                        @foreach ($attendance_leave_holiday_workshift['attendance_records'] as $index => $attendance_record)
                                                            <tr class="table_row2">
                                                               {{-- 1ST COL START AT --}}
<td style="padding-left:5px;">
    @php
        $in_time = \Carbon\Carbon::parse($attendance_record->in_time);
        $out_time = \Carbon\Carbon::parse($attendance_record->out_time);
        $show_date = $in_time->toDateString() !== $out_time->toDateString();
    @endphp

    {{ $show_date ? $in_time->format('d-m-y h:i A') : $in_time->format('h:i A') }}
</td>

{{-- 2ND COL END AT --}}
<td>
    @if (
        ($index == count($attendance_leave_holiday_workshift['attendance_records']) - 1) &&
        $attendance_record->in_time == $attendance_record->out_time
    )
        {{ 'Ongoing' }}
    @else
        {{ $show_date ? $out_time->format('d-m-y h:i A') : $out_time->format('h:i A') }}
    @endif
</td>

                                                                {{-- 4TH COL TOTAL  --}}
                                                                <td style="text-align: center;">
                                                                    {{ convertFloatHoursToTime(\Carbon\Carbon::parse($attendance_record->out_time)->diffInSeconds(\Carbon\Carbon::parse($attendance_record->in_time)) / (60 * 60)) }}
                                                                </td>

                                                                {{-- 5TH COL BREAK  --}}
                                                                <td style="text-align: center;">
                                                                    {{ convertFloatHoursToTime($attendance_record->break_hours) }}
                                                                    @if ($attendance_record->is_paid_break)
                                                                        (Paid)
                                                                    @elseif (!$attendance_record->is_paid_break)
                                                                        (Unpaid)
                                                                    @endif
                                                                </td>

                                                                {{-- 6TH COL PROJECTS  --}}
                                                                <td style="text-align: center;">
                                                                    @foreach ($attendance_record->projects as $project)
                                                                        <span>
                                                                            @if ($project->name)
                                                                                {{ $project->name }}
                                                                            @else
                                                                                N/A
                                                                            @endif
                                                                        </span>
                                                                    @endforeach
                                                                </td>

                                                                {{-- 7TH COL WORK SITE  --}}
                                                                <td style="text-align: center;">
                                                                    {{ $attendance_record->work_location->name }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @else
                                                        {{-- IF HAVE NO RECORD  --}}
                                                        <tr class="table_row2">
                                                            <td colspan="8" style="text-align: center;">No Record
                                                                Found
                                                                {{ count($attendance_leave_holiday_workshift['attendance_records']) }}
                                                            </td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        @else
                            {{-- IF HAVE NO RECORD  --}}
                            <tr class="table_row">
                                <td colspan="8" style="text-align: center;">No Data Found
                                    {{ count($employee['data']['data']) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <div>
            <p colspan="8" style="text-align: center;">No Employee Found</p>
        </div>
    @endif
</body>

</html>
