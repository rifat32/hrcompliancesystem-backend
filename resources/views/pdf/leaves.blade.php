<!DOCTYPE html>
<html>

<head>
    <title>Leave Request List</title>

    <!--ALL CUSTOM FUNCTIONS -->
    @php
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

        function convertTo12HourFormat($time)
        {
            return date('h:i A', strtotime($time)); // Convert time to 12-hour format
        }

        function formatLeaveDetails($d)
        {
            if ($d['start_date'] !== $d['end_date']) {
                return $d['start_date'] === $d['end_date']
                    ? format_date($d['start_date'])
                    : format_date($d['start_date']) . ' To ' . format_date($d['end_date']);
            } else {
                $output = format_date($d['date']);

                if (isset($d['leave_duration']) && $d['leave_duration'] === 'half_day') {
                    if (isset($d['day_type'])) {
                        $formatted_day_type = ucwords(str_replace('_', ' ', $d['day_type']));
                        $output .= " ( $formatted_day_type )";
                    }
                }

                if (isset($d['leave_duration']) && $d['leave_duration'] === 'hours' && isset($d['records'][0])) {
                    $start_time = convertTo12HourFormat($d['records'][0]['start_time']);
                    $end_time = convertTo12HourFormat($d['records'][0]['end_time']);
                    $output .= " ($start_time To $end_time)";
                }

                return $output;
            }
        }

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
            margin-top: 20px;
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

        .employee_name {}

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
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: {{ $color }};
            /* Set a background color (blue in this case) */
            color: white;
            /* Text color */
            font-size: 18px;
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
                <td class="file_title">Leaves</td>
            </tr>
            <tr>
                <td>
                    <span class="business_name">{{ $business->name }}</span>
                    <address class="business_address">{{ $business->address_line_1 }}</address>
                </td>
            </tr>
        </tbody>
    </table>


    <table>
        <h3>Leaves</h3>
        <thead>
            <tr class="table_head_row">
                <th class="index_col"></th>




                <th>Profile</th>

                <th>Date & Time</th>

                <th>Type</th>


                <th>Duration</th>

                <th>Total Leave Hours</th>

                <th>Status</th>


                {{-- <th>Attachment</th> --}}
            </tr>
        </thead>
        <tbody>
            @if (count($leaves))
                @foreach ($leaves as $index => $leave)
                    @php
                        $user = $leave->employee;
                    @endphp
                    <tr class="table_row">
                        <td class="index_col">{{ $index + 1 }}</td>
                        <td> {{ $user->title . ' ' . $user->first_Name . ' ' . $user->middle_Name . ' ' . $user->last_Name }}
                        </td>

                        <td>{{ formatLeaveDetails($leave) }}</td>



                        <td>{{ $leave->leave_type ? $leave->leave_type->name : '' }}</td>



                        <td>{{ ucwords(str_replace('_', ' ', $leave->leave_duration)) }}</td>



                        <td>
                            {{ convertFloatHoursToTime($leave->total_leave_hours) }}
                        </td>

                        <td>{{ $leave->status }}</td>

                        {{-- <td>{{ }}</td>
                        <td>{{ $user->is_active ? 'Active' : 'De-active' }}</td> --}}
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="8" style="text-align: center;">No Data Found</td>
                </tr>
            @endif
        </tbody>
    </table>

</body>

</html>
