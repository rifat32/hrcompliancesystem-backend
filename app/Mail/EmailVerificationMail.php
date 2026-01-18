<?php

namespace App\Mail;

use App\Http\Utils\BasicEmailUtil;
use App\Models\Business;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateWrapper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels, BasicEmailUtil;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $user;
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $user = $this->user;
        $contact_email = "";
        $business_id = null;
        $is_default = 1;

        if (!empty($user)) {
            $business_id = $user->business_id ?? null;
            $is_default = empty($business_id) ? 1 : 0;
            $contact_email = $user->business->email ?? $user->email ?? "asjadtariq@gmail.com";
        } else {
            $contact_email = "asjadtariq@gmail.com";
        }


        $email_content = EmailTemplate::where([
            "type" => "email_verification_mail",
            "is_active" => 1,
            "business_id" => $business_id,
            "is_default" => $is_default,

        ])->first();

        if (empty($email_content)) {
            $email_content = $this->storeEmailTemplateIfNotExists("email_verification_mail",NULL,0,FALSE);
           }


        $html_content = $email_content->template;
        $html_content =  str_replace("[FULL_NAME]", ($this->user->title . " " .$this->user->first_Name . " " . $this->user->middle_Name . " " . $this->user->last_Name . " "), $html_content);

        $html_content =  str_replace("[ACCOUNT_VERIFICATION_LINK]", (env('APP_URL') . '/activate/' . $this->user->email_verify_token), $html_content);
        $html_content =  str_replace("[CONTACT_EMAIL]", $contact_email, $html_content);
        $html_content =  str_replace("[APP_NAME]", env("APP_NAME"), $html_content);


        $subject = "Email verification from " . ($this->user->business?($this->user->business->name . " HRM"):env("APP_NAME"));
        return $this->subject($subject)->view('email.dynamic_mail', ["html_content" => $html_content]);



        // return $this->subject(("Welcome to " . env("APP_NAME") .  " - Please verify your email"))->view('email.verify_mail',["html_content"=>$html_content]);







    }
}
