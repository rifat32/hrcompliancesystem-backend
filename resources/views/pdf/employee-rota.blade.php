<!DOCTYPE html>
<html>

<head>
    <title>Employee Rota</title>

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

        function format_date($date)
        {
            return \Carbon\Carbon::parse($date)->format('d-m-Y');
        }

        $start_date = \Carbon\Carbon::parse(request()->start_date)->toDateString();
        $end_date = \Carbon\Carbon::parse(request()->end_date)->toDateString();

        $all_dates = collect(range(strtotime($start_date), strtotime($end_date), 86400)) // 86400 seconds in a day
            ->map(function ($timestamp) {
                return date('Y-m-d', $timestamp);
            });

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
    <style>
    table {
        border-collapse: collapse;
        width: 100%;
        font-size: 12px;
    }

    th, td {
        border: 1px solid #000;
        padding: 6px;
        vertical-align: top;
        text-align: center;
    }

    .header-table td {
        border: none;
        padding: 2px;
    }

    .logo {
        height: 60px;
    }

    .file_title {
        font-size: 20px;
        font-weight: bold;
        text-align: left;
    }

    .business_name {
        font-weight: bold;
    }

    .business_address {
        font-style: italic;
        font-size: 12px;
    }

    .css-logo {
        font-size: 20px;
        font-weight: bold;
        padding: 10px;
    }

    .shift-time {
        font-weight: bold;
    }

    .day-off {
        color: red;
        font-weight: bold;
    }
</style>

</head>

<body>

  <table class="header-table" style="margin-bottom: 20px;">
    <tr>
        @php
            $logo_path = public_path($business->logo);
        @endphp

        @if ($business->logo && file_exists($logo_path))
            <td rowspan="2">
                <img class="logo" src="{{ asset($business->logo) }}">
            </td>
        @else
            <td rowspan="2">
                <div class="css-logo">{{ $business->name }}</div>
            </td>
        @endif
        <td class="file_title">Schedules</td>
    </tr>
    <tr>
        <td>
            <span class="business_name">{{ $business->name }}</span><br>
            <address class="business_address">{{ $business->address_line_1 }}</address>
        </td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th>Employee</th>
            @foreach ($all_dates as $date)
                <th>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($employees as $employee)
            <tr>
                <td>
                    {{ $employee['first_Name'] . ' ' . $employee['middle_Name'] . ' ' . $employee['last_Name'] }}
                </td>

                @foreach ($employee['entries']['schedule']['schedule_data'] as $schedule_data)
                    <td>
                        @if (!empty($schedule_data['shifts']))
                            @if ($schedule_data['is_weekend'] ?? false)
                                <div class="day-off">Day Off</div>
                            @else
                                @foreach ($schedule_data['shifts'] as $shift)
                                    <div class="shift-time">
                                        {{ \Carbon\Carbon::parse($shift['start_at'])->format('H:i') }} -
                                        {{ \Carbon\Carbon::parse($shift['end_at'])->format('H:i') }}
                                    </div>
                                @endforeach
                                <div>
                                    {{ strtoupper($schedule_data['break_type']) === 'UNPAID' ? 'UPB' : 'PB' }}
                                    {{ $schedule_data['break_hours'] }} hr,
                                    TH {{ $schedule_data['capacity_hours'] }}
                                </div>
                            @endif
                        @else
                            <div class="day-off">Day Off</div>
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>


</body>

</html>
