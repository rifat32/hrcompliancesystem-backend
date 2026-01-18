<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class AttendanceSummaryExport implements FromView
{
    protected $employees;

    public function __construct($employees)
    {
        $this->employees = $employees;
    }

    public function view(): View
    {
        return view('export.attendance_summary', ["employees" => $this->employees]);
    }



    public function collection()
    {

    }

    public function map($user): array
    {
        // This method is still needed, even if it's empty for your case
        return [];
    }

}
