<!DOCTYPE html>
<html>

<head>
    <title>Employee</title>

    <!--ALL CUSTOM FUNCTIONS -->
    @php

        function format_date($date)
        {
            return \Carbon\Carbon::parse($date)->format('d-m-Y');
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
    @endphp

    @php
        // hour format
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

        // GETTING BUSINESS
        $business = auth()->user()->business;
        // GETTING LEAVE

        


        // GETTING ATTENDANCE

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
            box-sizing: border-box
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

        .index_col {
            width: 5px;
        }

        .empty_table_row {}

        .table_row td {
            padding: 10px 0px;
            border-bottom: 0.2px solid #ddd;
        }

        .employee {
            color: {{ $color }};
            /*font-weight:600;*/
        }

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
                <td class="file_title">Employee</td>
            </tr>
            <tr>
                <td>
                    <span class="business_name">{{ $business->name }}</span>
                    <address class="business_address">{{ $business->address_line_1 }}</address>
                </td>
            </tr>
        </tbody>
    </table>


    {{-- ALL TABLES  --}}

    {{-- *1. PERSONAL DETAILS  --}}
    @if (!empty($request->employee_details))
        <div>
            <h5>Employee Details</h5>

            <table>
                <thead>
                    <tr class="table_head_row">
                        @if (!empty($request->employee_details_name))
                            <th>Name</th>
                        @endif
                        @if (!empty($request->employee_details_user_id))
                            <th>Employe ID</th>
                        @endif
                        @if (!empty($request->employee_details_email))
                            <th>Email</th>
                        @endif
                        @if (!empty($request->employee_details_phone))
                            <th>Phone</th>
                        @endif
                        @if (!empty($request->employee_details_gender))
                            <th>Gender</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    <tr class="table_row">
                        @if (!empty($request->employee_details_name))
                            <td>{{ $user->title . ' ' . $user->first_Name . ' ' . $user->middle_Name . ' ' . $user->last_Name }}
                            </td>
                        @endif
                        @if (!empty($request->employee_details_user_id))
                            <td>{{ $user->user_id }}</td>
                        @endif
                        @if (!empty($request->employee_details_email))
                            <td>{{ $user->email }}</td>
                        @endif
                        @if (!empty($request->employee_details_phone))
                            <td>{{ $user->phone }}</td>
                        @endif
                        @if (!empty($request->employee_details_gender))
                            <td>{{ $user->gender }}</td>
                        @endif
                    </tr>
                </tbody>
            </table>
        </div>

    @endif



    {{-- *3. ATTENDANCE  --}}
    @if (!empty($request->attendances))
        <div>
            <h5>Attendances</h5>

            <table>
                <thead>
                    <tr class="table_head_row">
                        <th class="index_col"></th>
                        @if (!empty($request->attendance_date))
                            <th>Date</th>
                        @endif
                        @if (!empty($request->attendance_start_time))
                            <th>Start Time</th>
                        @endif
                        @if (!empty($request->attendance_end_time))
                            <th>End Time</th>
                        @endif
                        @if (!empty($request->attendance_break))
                            <th>Break (hour)</th>
                        @endif
                        @if (!empty($request->attendance_schedule))
                            <th>Schedule (hour)</th>
                        @endif
                        @if (!empty($request->attendance_overtime))
                            <th>Overtime (hour)</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if (count($user->attendances))
                        @foreach ($user->attendances as $index => $attendance)
                            <tr class="table_row">
                                <td class="index_col">{{ $index + 1 }}. </td>
                                @if (!empty($request->attendance_date))
                                    <td>{{ format_date($attendance->in_date) }}</td>
                                @endif
                                @if (!empty($request->attendance_start_time))
                                    <td>{{ $attendance->in_time }}</td>
                                @endif
                                @if (!empty($request->attendance_end_time))
                                    <td>{{ $attendance->out_time }}</td>
                                @endif
                                @if (!empty($request->attendance_break))
                                    <td>{{ $attendance->does_break_taken ? time_format($attendance->break_hours) : 0 }}
                                    </td>
                                @endif
                                @if (!empty($request->attendance_schedule))
                                    <td>{{ time_format($attendance->capacity_hours) }}</td>
                                @endif
                                @if (!empty($request->attendance_overtime))
                                    <td>{{ time_format($attendance->overtime_hours) }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @else
                        <tr class="table_row">
                            <td colspan="8" style="text-align: center;">No Data Found</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif

    {{-- *4. LEAVE  --}}
    @if (!empty($request->leaves))
        <div>
            <h5>Leaves</h5>

            <table>
                <thead>
                    <tr class="table_head_row">
                        <th class="index_col"></th>
                        @if (!empty($request->leave_date_time))
                            <th>Date & Time</th>
                        @endif
                        @if (!empty($request->leave_type))
                            <th>Type</th>
                        @endif
                        @if (!empty($request->leave_duration))
                            <th>Duration</th>
                        @endif
                        @if (!empty($request->total_leave_hours))
                            <th>Total Leave (hours)</th>
                        @endif
                        {{-- <th>Attachment</th> --}}
                    </tr>
                </thead>
                <tbody>
                    @if (count($user->leaves))
                        @foreach ($user->leaves as $index => $leave)
                            <tr class="table_row">
                                <td class="index_col">{{ $index + 1 }}. </td>
                                @if (!empty($request->leave_date_time))
                                    <td>{{ format_date($leave->start_date) }} - {{ format_date($leave->end_date) }}
                                    </td>
                                @endif
                                @if (!empty($request->leave_type))
                                    <td>{{ $leave->leave_type ? $leave->leave_type->name : '' }}</td>
                                @endif
                                @if (!empty($request->leave_duration))
                                    <td>{{ $leave->leave_duration }}</td>
                                @endif
                                @if (!empty($request->total_leave_hours))
                                    <td>
                                        @php
                                            $leave->total_leave_hours = $leave->records->sum(function ($record) {
                                                $startTime = \Carbon\Carbon::parse($record->start_time);
                                                $endTime = \Carbon\Carbon::parse($record->end_time);
                                                $total_seconds = $startTime->diffInSeconds($endTime);
                                                return round($total_seconds / 3600, 2); // Converts seconds to hours and rounds to 2 decimals
                                            });
                                        @endphp
                                        {{ time_format($leave->total_leave_hours) }}
                                    </td>
                                @endif
                                {{-- <td>{{ }}</td>
                        <td>{{ $user->is_active ? 'Active' : 'De-active' }}</td> --}}
                            </tr>
                        @endforeach
                    @else
                        <tr class="table_row">
                            <td colspan="8" style="text-align: center;">No Data Found</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif

    {{-- *5. DOCUMENTS  --}}
    @if (!empty($request->documents))
        <div>
            <h5>Documents</h5>

            <table>
                <thead>
                    <tr class="table_head_row">
                        <th class="index_col"></th>
                        @if (!empty($request->document_title))
                            <th>Title</th>
                        @endif
                        {{-- <th>Attachment</th> --}}
                        @if (!empty($request->document_added_by))
                            <th>Added by</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if (count($user->documents))
                        @foreach ($user->documents as $index => $document)
                            <tr class="table_row">
                                <td class="index_col">{{ $index + 1 }}. </td>
                                @if (!empty($request->document_title))
                                    <td>{{ $document->name }}</td>
                                @endif
                                @if (!empty($request->document_added_by))
                                    <td>{{ $document->creator->title . ' ' . $document->creator->first_Name . ' ' . $document->creator->last_Name . ' ' . $document->creator->middle_Name }}
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    @else
                        <tr class="table_row">
                            <td colspan="8" style="text-align: center;">No Data Found</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif

    {{-- *6. ASSETS  --}}
    @if (!empty($request->assets))
        <div>
            <h5>Assets</h5>

            <table>
                <thead>
                    <tr class="table_head_row">
                        <th class="index_col"></th>
                        @if (!empty($request->asset_name))
                            <th>Asset Name</th>
                        @endif
                        @if (!empty($request->asset_code))
                            <th>Asset Code</th>
                        @endif
                        @if (!empty($request->asset_serial_number))
                            <th>Serial No</th>
                        @endif
                        @if (!empty($request->asset_is_working))
                            <th>Is Working</th>
                        @endif
                        @if (!empty($request->asset_type))
                            <th>Type</th>
                        @endif
                        @if (!empty($request->asset_date))
                            <th>Date</th>
                        @endif
                        @if (!empty($request->asset_note))
                            <th>Note</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if (count($user->assets))
                        @foreach ($user->assets as $index => $asset)
                            <tr class="table_row">
                                <td class="index_col">.</td>
                                @if (!empty($request->asset_name))
                                    <td>{{ $asset->name }}</td>
                                @endif
                                @if (!empty($request->asset_code))
                                    <td>{{ $asset->code }}</td>
                                @endif
                                @if (!empty($request->asset_serial_number))
                                    <td>{{ $asset->serial_number }}</td>
                                @endif
                                @if (!empty($request->asset_is_working))
                                    <td>{{ $asset->is_working }}</td>
                                @endif
                                @if (!empty($request->asset_type))
                                    <td>{{ $asset->type }}</td>
                                @endif
                                @if (!empty($request->asset_date))
                                    <td>{{ format_date($asset->date) }}</td>
                                @endif
                                @if (!empty($request->asset_note))
                                    <td>{{ $asset->note }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @else
                        <tr class="table_row">
                            <td colspan="8" style="text-align: center;">No Data Found</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif

    {{-- *7. EDUCATIONAL HISTORY  --}}
    @if (!empty($request->educational_history))
        <div>
            <h5>Educational History</h5>
            <table>
                <thead>
                    <tr class="table_head_row">
                        <th class="index_col"></th>
                        @if (!empty($request->educational_history_degree))
                            <th>Degree</th>
                        @endif
                        @if (!empty($request->educational_history_major))
                            <th>Major</th>
                        @endif
                        {{-- Uncomment if you want to include institution --}}
                        {{-- <th>Institution</th> --}}
                        @if (!empty($request->educational_history_start_date))
                            <th>Start Date</th>
                        @endif
                        @if (!empty($request->educational_history_achievements))
                            <th>Achievements</th>
                        @endif
                        {{-- Uncomment if you want to include description --}}
                        {{-- <th>Description</th> --}}
                    </tr>
                </thead>
                <tbody>
                    @if (count($user->education_histories))
                        @foreach ($user->education_histories as $index => $education_history)
                            <tr class="table_row">
                                <td style="padding:0px 10px">{{ $index + 1 }}. </td>
                                @if (!empty($request->educational_history_degree))
                                    <td>{{ $education_history->degree }}</td>
                                @endif
                                @if (!empty($request->educational_history_major))
                                    <td>{{ $education_history->major }}</td>
                                @endif
                                @if (!empty($request->educational_history_start_date))
                                    <td>{{ format_date($education_history->start_date) }}</td>
                                @endif
                                @if (!empty($request->educational_history_achievements))
                                    <td>{{ $education_history->achievements }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @else
                        <tr class="table_row">
                            <td colspan="8" style="text-align: center;">No Data Found</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif

    {{-- *8.JOB HISTORY  --}}
    @if (!empty($request->job_history))
        <div>
            <h5>Job History</h5>

            <table>
                <thead>
                    <tr class="table_head_row">
                        <th class="index_col"></th>
                        @if (!empty($request->job_history_job_title))
                            <th>Job Title</th>
                        @endif
                        @if (!empty($request->job_history_company))
                            <th>Company</th>
                        @endif
                        @if (!empty($request->job_history_start_on))
                            <th>Start On</th>
                        @endif
                        @if (!empty($request->job_history_end_at))
                            <th>End At</th>
                        @endif
                        @if (!empty($request->job_history_supervisor))
                            <th>Supervisor</th>
                        @endif
                        {{-- <th>Contact Info</th> JAGA HOILE DIO EDI --}}
                        @if (!empty($request->job_history_country))
                            <th>Country</th>
                        @endif
                        {{-- <th>Achivments</th> JAGA HOILE DIO EDI --}}
                    </tr>
                </thead>
                <tbody>
                    @if (count($user->job_histories))
                        @foreach ($user->job_histories as $index => $job_history)
                            <tr class="table_row">
                                <td class="index_col">{{ $index + 1 }}. </td>

                                @if (!empty($request->job_history_job_title))
                                    <td>{{ $job_history->job_title }}</td>
                                @endif
                                @if (!empty($request->job_history_company))
                                    <td>{{ $job_history->company_name }}</td>
                                @endif
                                {{-- <td>{{ \Carbon\Carbon::parse($job_history->employment_start_date)->format('Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($job_history->employment_start_date)->format('F') }}</td> --}}
                                @if (!empty($request->job_history_start_on))
                                    <td>{{ format_date($job_history->employment_start_date) }}</td>
                                @endif
                                @if (!empty($request->job_history_end_at))
                                    <td>{{ format_date($job_history->employment_end_date) }}</td>
                                @endif
                                @if (!empty($request->job_history_supervisor))
                                    <td>{{ $job_history->supervisor_name }}</td>
                                @endif
                                @if (!empty($request->job_history_country))
                                    <td>{{ $job_history->work_location }}</td>
                                @endif

                            </tr>
                        @endforeach
                    @else
                        <tr class="table_row">
                            <td colspan="8" style="text-align: center;">No Data Found</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif

    {{-- *9. IMMIGRATION DERAILS  --}}
    {{-- *====================  --}}
    {{-- *COS HISTORY --}}
    @if (!empty($request->current_cos_details))
        <div>
            <h5> Current COS Details</h5>

            <table>
                <thead>
                    <tr class="table_head_row">
                        <th class="index_col"></th>
                        @if (!empty($request->current_cos_details_date_assigned))
                            <th>Date Assigned</th>
                        @endif
                        @if (!empty($request->current_cos_details_expiry_date))
                            <th>Expiry Date</th>
                        @endif
                        @if (!empty($request->current_cos_details_certificate_number))
                            <th>Certificate Number</th>
                        @endif
                        @if (!empty($request->current_cos_details_current_certificate_status))
                            <th>Status</th>
                        @endif
                        @if (!empty($request->current_cos_details_note))
                            <th>Note</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if (!empty($user->sponsorship_details))
                        <tr class="table_row">
                            <td class="index_col">1</td>
                            @if (!empty($request->current_cos_details_date_assigned))
                                <td>{{ format_date($user->sponsorship_details->date_assigned) }}</td>
                            @endif
                            @if (!empty($request->current_cos_details_expiry_date))
                                <td>{{ format_date($user->sponsorship_details->expiry_date) }}</td>
                            @endif
                            @if (!empty($request->current_cos_details_certificate_number))
                                <td>{{ $user->sponsorship_details->certificate_number }}</td>
                            @endif
                            @if (!empty($request->current_cos_details_current_certificate_status))
                                <td>{{ $user->sponsorship_details->current_certificate_status }}</td>
                            @endif
                            @if (!empty($request->current_cos_details_note))
                                <td>{{ $user->sponsorship_details->note }}</td>
                            @endif
                        </tr>
                    @else
                        <tr class="table_row">
                            <td colspan="8" style="text-align: center;">No Data Found</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif

    {{-- *PENSION DETAIL --}}
    @if (!empty($request->current_pension_details))
        @if (!$user->pension_details->pension_eligible)
            <div>
                <h5> Current Pension Details</h5>

                <table>
                    <thead>
                        <tr class="table_head_row">
                            <th class="index_col"></th>
                            @if (!empty($request->current_pension_details_pension_scheme_status))
                                <th>Status</th>
                            @endif
                            @if (!empty($request->current_pension_details_pension_enrollment_issue_date))
                                <th>Issue Date</th>
                            @endif
                            @if (!empty($request->current_pension_details_pension_scheme_opt_out_date))
                                <th>Opt Out Date</th>
                            @endif
                            @if (!empty($request->current_pension_details_pension_re_enrollment_due_date))
                                <th>Re Enrollment Due Date</th>
                            @endif

                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($user->pension_details))
                            <tr class="table_row">
                                <td class="index_col">1</td>
                                @if (!empty($request->current_pension_details_pension_scheme_status))
                                    <td>{{ format_date($user->pension_details->pension_scheme_status) }}</td>
                                @endif
                                @if (!empty($request->current_pension_details_pension_enrollment_issue_date))
                                    <td>{{ format_date($user->pension_details->pension_enrollment_issue_date) }}</td>
                                @endif
                                @if (!empty($request->current_pension_details_pension_scheme_opt_out_date))
                                    <td>{{ $user->pension_details->pension_scheme_opt_out_date }}</td>
                                @endif
                                @if (!empty($request->current_pension_details_pension_re_enrollment_due_date))
                                    <td>{{ $user->pension_details->pension_re_enrollment_due_date }}</td>
                                @endif

                            </tr>
                        @else
                            <tr class="table_row">
                                <td colspan="8" style="text-align: center;">No Data Found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        @else
            <div>
                <h5> Current Pension Details</h5>

                <table>
                    <thead>
                        <tr class="table_head_row">
                            <th class="index_col"></th>
                            @if (!empty($request->current_pension_details_pension_scheme_status))
                                <th>Status</th>
                            @endif
                            @if (!empty($request->current_pension_details_pension_enrollment_issue_date))
                                <th>Issue Date</th>
                            @endif
                            @if (!empty($request->current_pension_details_pension_scheme_opt_out_date))
                                <th>Opt Out Date</th>
                            @endif
                            @if (!empty($request->current_pension_details_pension_re_enrollment_due_date))
                                <th>Re Enrollment Due Date</th>
                            @endif

                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table_row">
                            <td colspan="8" style="text-align: center;">This user pension is not valid</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    @endif

    {{-- *PASSPORT HISTORY --}}
    @if (!empty($request->current_passport_details))
        <div>
            <h5>Current Passport Details</h5>

            <table>
                <thead>
                    <tr class="table_head_row">
                        <th class="index_col"></th>
                        @if (!empty($request->current_passport_details_issue_date))
                            <th>Issue Date</th>
                        @endif
                        @if (!empty($request->current_passport_details_expiry_date))
                            <th>Expiry Date</th>
                        @endif
                        @if (!empty($request->current_passport_details_passport_number))
                            <th>Passport Number</th>
                        @endif
                        @if (!empty($request->current_passport_details_place_of_issue))
                            <th>Place Of Issue</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if (!empty($user->passport_details))
                        <tr class="table_row">
                            <td class="index_col">1</td>
                            @if (!empty($request->current_passport_details_issue_date))
                                <td>{{ format_date($user->passport_details->passport_issue_date) }}</td>
                            @endif
                            @if (!empty($request->current_passport_details_expiry_date))
                                <td>{{ format_date($user->passport_details->passport_expiry_date) }}</td>
                            @endif
                            @if (!empty($request->current_passport_details_passport_number))
                                <td>{{ $user->passport_details->passport_number }}</td>
                            @endif
                            @if (!empty($request->current_passport_details_place_of_issue))
                                <td>{{ $user->passport_details->place_of_issue }}</td>
                            @endif
                        </tr>
                    @else
                        <tr class="table_row">
                            <td colspan="8" style="text-align: center;">No Data Found</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif


    {{-- *VISA HISTORY --}}
    @if (!empty($request->current_visa_details))
        <div>
            <h5>Current Visa Details</h5>

            <table>
                <thead>
                    <tr class="table_head_row">
                        <th class="index_col"></th>
                        @if (!empty($request->current_visa_details_issue_date))
                            <th>Issue Date</th>
                        @endif
                        @if (!empty($request->current_visa_details_expiry_date))
                            <th>Expiry Date</th>
                        @endif
                        @if (!empty($request->current_visa_details_BRP_number))
                            <th>BRP Number</th>
                        @endif
                        @if (!empty($request->current_visa_details_place_of_issue))
                            <th>Place Of Issue</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if (!empty($user->visa_details))
                        <tr class="table_row">
                            <td class="index_col">1</td>
                            @if (!empty($request->current_visa_details_issue_date))
                                <td>{{ format_date($user->visa_details->visa_issue_date) }}</td>
                            @endif
                            @if (!empty($request->current_visa_details_expiry_date))
                                <td>{{ format_date($user->visa_details->visa_expiry_date) }}</td>
                            @endif
                            @if (!empty($request->current_visa_details_BRP_number))
                                <td>{{ $user->visa_details->BRP_number }}</td>
                            @endif
                            @if (!empty($request->current_visa_details_place_of_issue))
                                <td>{{ $user->visa_details->place_of_issue }}</td>
                            @endif
                        </tr>
                    @else
                        <tr class="table_row">
                            <td colspan="8" style="text-align: center;">No Data Found</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif

    {{-- *RIGHT TO WORK HISTORY  --}}
    @if (!empty($request->current_right_to_works))
        <div>
            <h5>Current Right To Works</h5>

            <table>
                <thead>
                    <tr class="table_head_row">
                        <th class="index_col">1</th>
                        @if (!empty($request->current_right_to_works_right_to_work_check_date))
                            <th>Check Date</th>
                        @endif
                        @if (!empty($request->current_right_to_works_right_to_work_expiry_date))
                            <th>Expiry Date</th>
                        @endif
                        @if (!empty($request->current_right_to_works_right_to_work_code))
                            <th>Code</th>
                        @endif




                    </tr>
                </thead>
                <tbody>
                    @if (!empty($user->right_to_works))

                        <tr class="table_row">
                            {{-- <td>{{ $user->visa_details->created_at }}</td>
                    <td>{{ $user->visa_details->updated_at }}</td> --}}
                            <td class="index_col">1</td>



                            @if (!empty($request->current_right_to_works_right_to_work_check_date))
                                <td>{{ format_date($user->right_to_works->right_to_work_check_date) }}</td>
                            @endif


                            @if (!empty($request->current_right_to_works_right_to_work_expiry_date))
                                <td>{{ format_date($user->right_to_works->right_to_work_expiry_date) }}</td>
                            @endif

                            @if (!empty($request->current_right_to_works_right_to_work_code))
                                <td>{{ $user->right_to_works->right_to_work_code }}</td>
                            @endif

                        </tr>


                    @endif
                </tbody>
            </table>
        </div>
    @endif
    {{-- *=======================  --}}

    {{-- *10. ADDRESS DETAILS  --}}
    @if (!empty($request->address_details))
        <div>
            <h5>Address Details</h5>

            <table>
                <thead>
                    <tr class="table_head_row">
                        @if (!empty($request->address_details_address))
                            <th>Address</th>
                        @endif
                        @if (!empty($request->address_details_city))
                            <th>City</th>
                        @endif
                        @if (!empty($request->address_details_country))
                            <th>Country</th>
                        @endif
                        @if (!empty($request->address_details_postcode))
                            <th>Postcode</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if (!empty($user->address_line_1))
                        <tr class="table_row">
                            @if (!empty($request->address_details_address))
                                <td>{{ $user->address_line_1 }}</td>
                            @endif
                            @if (!empty($request->address_details_city))
                                <td>{{ $user->city }}</td>
                            @endif
                            @if (!empty($request->address_details_country))
                                <td>{{ $user->country }}</td>
                            @endif
                            @if (!empty($request->address_details_postcode))
                                <td>{{ $user->postcode }}</td>
                            @endif
                        </tr>
                    @else
                        <tr class="table_row">
                            <td colspan="8" style="text-align: center;">No Data Found</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif


    {{-- *11. CONTACT  --}}
    @if (!empty($request->contact_details))
        <div>
            <h5>Contact Details</h5>

            <table>
                <thead>
                    <tr class="table_head_row">
                        <th class="index_col"></th>
                        @if (!empty($request->contact_details_first_name))
                            <th>First Name</th>
                        @endif
                        @if (!empty($request->contact_details_last_name))
                            <th>Last Name</th>
                        @endif
                        @if (!empty($request->contact_details_relationship))
                            <th>Relationship To Employee</th>
                        @endif
                        @if (!empty($request->contact_details_address))
                            <th>Address</th>
                        @endif
                        @if (!empty($request->contact_details_postcode))
                            <th>Postcode</th>
                        @endif
                        @if (!empty($request->contact_details_day_time_tel_number))
                            <th style="text-transform: capitalize">Day Time Tel Number</th>
                        @endif
                        @if (!empty($request->contact_details_evening_time_tel_number))
                            <th style="text-transform: capitalize">Evening Time Tel Number</th>
                        @endif
                        @if (!empty($request->contact_details_mobile_tel_number))
                            <th style="text-transform: capitalize">Mobile Tel Number</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if (count($user->emergency_contact_details))
                        @foreach ($user->emergency_contact_details as $index => $emergency_contact)
                            @if (!empty($emergency_contact['mobile_tel_number']))
                                <tr class="table_row">
                                    <td class="index_col">{{ $index + 1 }}. </td>

                                    @if (!empty($request->contact_details_first_name))
                                        <td>{{ isset($emergency_contact['first_Name']) ? $emergency_contact['first_Name'] : '' }}
                                        </td>
                                    @endif
                                    @if (!empty($request->contact_details_last_name))
                                        <td>{{ isset($emergency_contact['last_Name']) ? $emergency_contact['last_Name'] : '' }}
                                        </td>
                                    @endif
                                    @if (!empty($request->contact_details_relationship))
                                        <td>{{ isset($emergency_contact['relationship_of_above_to_you']) ? $emergency_contact['relationship_of_above_to_you'] : '' }}
                                        </td>
                                    @endif
                                    @if (!empty($request->contact_details_address))
                                        <td>{{ isset($emergency_contact['address_line_1']) ? $emergency_contact['address_line_1'] : '' }}
                                        </td>
                                    @endif
                                    @if (!empty($request->contact_details_postcode))
                                        <td>{{ isset($emergency_contact['postcode']) ? $emergency_contact['postcode'] : '' }}
                                        </td>
                                    @endif
                                    @if (!empty($request->contact_details_day_time_tel_number))
                                        <td>{{ isset($emergency_contact['day_time_tel_number']) ? $emergency_contact['day_time_tel_number'] : '' }}
                                        </td>
                                    @endif
                                    @if (!empty($request->contact_details_evening_time_tel_number))
                                        <td>{{ isset($emergency_contact['evening_time_tel_number']) ? $emergency_contact['evening_time_tel_number'] : '' }}
                                        </td>
                                    @endif
                                    @if (!empty($request->contact_details_mobile_tel_number))
                                        <td>{{ isset($emergency_contact['mobile_tel_number']) ? $emergency_contact['mobile_tel_number'] : '' }}
                                        </td>
                                    @endif

                                </tr>
                            @endif
                        @endforeach
                    @else
                        <tr class="table_row">
                            <td colspan="8" style="text-align: center;">No Data Found</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif

    {{-- *12. NOTES  --}}
    @if (!empty($request->notes))
        <div>
            <h5>Notes</h5>

            <table>
                <thead>
                    <tr class="table_head_row">
                        <th class="index_col"></th>
                        @if (!empty($request->notes_title))
                            <th>Title</th>
                        @endif
                        @if (!empty($request->notes_description))
                            <th>Description</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if (count($user->notes))
                        @foreach ($user->notes as $index => $note)
                            <tr class="table_row">
                                <td class="index_col">{{ $index + 1 }}. </td>
                                @if (!empty($request->notes_title))
                                    <td>{{ $note->title }}</td>
                                @endif
                                @if (!empty($request->notes_description))
                                    <td>{{ $note->description }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @else
                        <tr class="table_row">
                            <td colspan="8" style="text-align: center;">No Data Found</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif

    {{-- *13. BANK DETAILS  --}}
    @if (!empty($request->bank_details))
        <div>
            <h5>Bank Details</h5>

            <table>
                <thead>
                    <tr class="table_head_row">
                        <th class="index_col"></th>
                        @if (!empty($request->bank_details_name))
                            <th>Bank Name</th>
                        @endif
                        @if (!empty($request->bank_details_sort_code))
                            <th>Sort Code</th>
                        @endif
                        @if (!empty($request->bank_details_account_name))
                            <th>Account Name</th>
                        @endif
                        @if (!empty($request->bank_details_account_number))
                            <th>Account Number</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if (!empty($user->bank_details_name))
                        <tr class="table_row">
                            <td class="index_col">1</td>
                            @if (!empty($request->bank_details_name))
                                <td>{{ $user->bank->name }}</td>
                            @endif
                            @if (!empty($request->bank_details_sort_code))
                                <td>{{ $user->sort_code }}</td>
                            @endif
                            @if (!empty($request->bank_details_account_name))
                                <td>{{ $user->account_name }}</td>
                            @endif
                            @if (!empty($request->bank_details_account_number))
                                <td>{{ $user->account_number }}</td>
                            @endif
                        </tr>
                    @else
                        <tr class="table_row">
                            <td colspan="8" style="text-align: center;">No Data Found</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif


    {{-- *14. SOCIAL LINKS  --}}
    @if (!empty($request->social_links))
        <div>
            <h5>Social Links</h5>

            <table>
                <thead>
                    <tr class="table_head_row">
                        <th class="index_col"></th>
                        @if (!empty($request->social_links_website))
                            <th>Website</th>
                        @endif
                        @if (!empty($request->social_links_url))
                            <th>URL</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if (count($user->social_links))
                        @foreach ($user->social_links as $index => $social_link)
                            <tr class="table_row">
                                <td class="index_col">{{ $index + 1 }}. </td>
                                @if (!empty($request->social_links_website))
                                    <td>{{ $social_link->social_site->name }}</td>
                                @endif
                                @if (!empty($request->social_links_url))
                                    <td>{{ $social_link->profile_link }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @else
                        <tr class="table_row">
                            <td colspan="3" style="text-align: center;">No Data Found</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif
</body>

</html>
