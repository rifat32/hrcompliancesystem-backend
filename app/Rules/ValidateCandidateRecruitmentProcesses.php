<?php

namespace App\Rules;

use App\Models\CandidateRecruitmentProcess;
use App\Models\UserRecruitmentProcess;
use Exception;
use Illuminate\Contracts\Validation\Rule;

class ValidateCandidateRecruitmentProcesses implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    private $candidate_id;

    public function __construct($candidate_id)
    {

        $this->candidate_id = $candidate_id;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {


        $candidateRecruitmentProcess = CandidateRecruitmentProcess::where([
            'candidate_recruitment_processes.candidate_id' => $this->candidate_id,
            'candidate_recruitment_processes.id' => $value,
        ])
        ->first();


        return $candidateRecruitmentProcess?1:0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute is invalid.';
    }
}
