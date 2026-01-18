<?php

namespace App\Mail;

use App\Http\Utils\BasicEmailUtil;
use App\Models\Business;
use App\Models\EmailTemplate;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JobApplicationReceivedMail extends Mailable
{
    use Queueable, SerializesModels, BasicEmailUtil;

     /**
     * Create a new message instance.
     *
     * @return void
     */



     private $candidate;






     public function __construct($candidate = null)
     {

         $this->candidate = $candidate->load(["job_listing","business"]);


     }

     /**
      * Build the message.
      *
      * @return $this
      */
     public function build()
     {



         $job_listing = $this->candidate->job_listing;
         $business = $this->candidate->business;

         $business_id = null;
         $is_default = 1;

         if (!empty($user)) {
             $business_id = $user->business_id ?? null;
         }
         $is_default = empty($business_id) ? 1 : 0;

         $email_content = EmailTemplate::where([
             "type" => "job_application_received_mail",
             "is_active" => 1,
             "business_id" => $business_id,
             "is_default" => $is_default,

         ])->first();

         if (empty($email_content)) {
          $email_content = $this->storeEmailTemplateIfNotExists("job_application_received_mail",$business_id,$is_default,TRUE);
         }







         $html_content = $email_content->template;

         $html_content =  str_replace("[FULL_NAME]", ($this->candidate->name), $html_content);

         $html_content =  str_replace("[JOB_TITLE]", $job_listing->title, $html_content);

         $html_content =  str_replace("[COMPANY_NAME]", $business->name, $html_content);

         $html_content =  str_replace("[CONTACT_EMAIL]", $business->email, $html_content);

         $html_content =  str_replace("[APPLICATION_DATE]", Carbon::parse($this->candidate->application_date)->format('M d, Y'), $html_content);



         $subject = "Job application received from " . ($business?($business->name . " HRM"):env("APP_NAME"));
         return $this->subject($subject)->view('email.dynamic_mail', ["html_content" => $html_content]);

        }



    }

