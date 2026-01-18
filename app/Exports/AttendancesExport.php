<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;

class AttendancesExport implements FromView
{

    protected $attendances;

    public function __construct($attendances)
    {
        $this->attendances = $attendances;
    }

    public function view(): View
    {
        return view('export.attendances', ["attendances" => $this->attendances]);
    }

    public function role_string($inputString) {
        // Remove underscore
        $withoutUnderscore = str_replace('_', '', $inputString);

        // Remove everything from the pound sign (#) and onwards
        $finalString = explode('#', $withoutUnderscore)[0];

        // Extract the role part (e.g., 'admin' or 'employee')
        $rolePart = str_replace('business_', '', $finalString);

        return $rolePart;
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
