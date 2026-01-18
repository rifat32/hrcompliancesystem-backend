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

        .employee_index {}

        .employee {
            color: {{ $color }};
            /*font-weight:600;*/
        }

        .employee_details {
            padding: 5px 10px 10px;
        }

        .employee_attendance_summary {
            margin-top: 0;
            margin-bottom: 0;
            margin-left: 18px;
            font-size: 12px;
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
                        <div class="css-logo">
                            {{ $business->name }}
                        </div>
                    </td>
                @endif
                <td></td>
            </tr>
            <tr>
                <td class="file_title">Attendance Summary</td>
            </tr>
            <tr>
                <td>
                    <span class="business_name">{{ $business->name }}</span>
                    <address class="business_address">{{ $business->address_line_1 }}</address>
                </td>
            </tr>
        </tbody>
    </table>


    @if (count($employees))
        @foreach ($employees as $index => $employee)
            <div style="border: 2px solid #aaa;margin-bottom: 10px; border-radius: 10px; overflow: hidden;">
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
                                <td>
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
                            <th>Type</th>
                            <th>Scheduled</th>
                            <th>Worked</th>
                            <th>Break</th>
                            <th>Present/Absent</th>
                            <th>Overtime</th>
                            <th style="padding-right: 5px;">Lateness status</th>
                        </tr>
                    </thead>

                    {{-- RECORD BODY --}}
                    <tbody>
                        {{-- IF HAVE ANY RECORDS --}}
                        @if (count($employee['data']['data']))
                            @foreach ($employee['data']['data'] as $attendance_leave_holiday_workshift)
                                <tr class="table_row">
                                    {{-- DATE  --}}
                                    @if ($attendance_leave_holiday_workshift->type == 'holiday')
                                        <td style="padding-left:5px;font-weight:bold;color:green">
                                            {{ format_date($attendance_leave_holiday_workshift->in_date) }}
                                        </td>
                                    @elseif ($attendance_leave_holiday_workshift->type == 'leave_record')
                                        <td style="padding-left:5px;font-weight:bold;color:red">
                                            {{ format_date($attendance_leave_holiday_workshift->in_date) }}
                                        </td>
                                    @elseif ($attendance_leave_holiday_workshift->type == 'work_shift_detail')
                                        <td style="padding-left: 5px;">
                                            {{ format_date($attendance_leave_holiday_workshift->in_date) }}
                                        </td>
                                    @elseif ($attendance_leave_holiday_workshift->type == 'attendance')
                                        <td style="padding-left: 5px;">
                                            {{ format_date($attendance_leave_holiday_workshift->in_date) }}
                                        </td>
                                    @endif


                                    {{-- TYPE  --}}
                                    @if ($attendance_leave_holiday_workshift->type == 'holiday')
                                        <td colspan="8" style="text-align:center;font-weight:bold;color:green">
                                            Holiday</td>
                                    @elseif ($attendance_leave_holiday_workshift->type == 'leave_record')
                                        <td colspan="8" style="text-align:center;font-weight:bold;color:red">On Leave
                                        </td>
                                    @elseif ($attendance_leave_holiday_workshift->type == 'work_shift_detail')
                                        <td></td>
                                    @elseif ($attendance_leave_holiday_workshift->type == 'attendance')
                                        <td></td>
                                    @endif

                                    {{-- SCHEDULED  --}}
                                    @if ($attendance_leave_holiday_workshift->type == 'attendance')
                                        <td>{{ convertFloatHoursToTime($attendance_leave_holiday_workshift->capacity_hours - $attendance_leave_holiday_workshift->leave_hours) }}
                                        </td>
                                    @elseif ($attendance_leave_holiday_workshift->type != 'holiday')
                                        <td> {{ convertFloatHoursToTime($attendance_leave_holiday_workshift->capacity_hours) }}
                                        </td>
                                    @endif

                                    {{-- WORKED  --}}
                                    <td>
                                        @if ($attendance_leave_holiday_workshift->type == 'attendance')
                                            {{ convertFloatHoursToTime($attendance_leave_holiday_workshift['total_paid_hours']) }}
                                        @endif
                                    </td>

                                    {{-- Break  --}}
                                    <td>
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

                                    <td style="text-align: center;">
                                        @if (
                                            $attendance_leave_holiday_workshift->type == 'attendance' ||
                                                $attendance_leave_holiday_workshift->type == 'work_shift_detail')
                                            {{ $attendance_leave_holiday_workshift->is_present ? 'P' : 'A' }}
                                        @endif
                                    </td>

                                    <td>
                                        @if ($attendance_leave_holiday_workshift->type == 'attendance')
                                            {{ convertFloatHoursToTime($attendance_leave_holiday_workshift['overtime_hours']) }}
                                        @endif
                                    </td>

                                    <td style="padding-right: 5px;">
                                        @if ($attendance_leave_holiday_workshift->type == 'attendance')
                                            {{ $attendance_leave_holiday_workshift->behavior }}
                                        @endif
                                    </td>
                                </tr>
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
