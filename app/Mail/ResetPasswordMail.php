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

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels, BasicEmailUtil;

    /**
     * Create a new message instance.
     *
     * @return void
     */



    private $user;
    private $client_site;





    public function __construct($user = null, $client_site = "")
    {

        $this->user = $user;

        $this->client_site = $client_site;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {


        $user = $this->user;
        $business_id = null;
        $is_default = 1;

        if (!empty($user)) {
            $business_id = $user->business_id ?? null;
            $is_default = empty($business_id) ? 1 : 0;
        }

        $email_content = EmailTemplate::where([
            "type" => "reset_password_mail",
            "is_active" => 1,
            "business_id" => $business_id,
            "is_default" => $is_default,

        ])->first();

        if (empty($email_content)) {
            $email_content = $this->storeEmailTemplateIfNotExists("reset_password_mail",$business_id,$is_default,TRUE);
           }

        if ($this->client_site == "client") {
            $front_end_url = env('FRONT_END_URL');
        } else if ($this->client_site == "dashboard") {
            $front_end_url = env('FRONT_END_URL');
        }




        $html_content = $email_content->template;

        $html_content =  str_replace("[RESET_PASSWORD_LINK]", ($front_end_url . '/auth/change-password?token=' . $this->user->resetPasswordToken), $html_content);


        $subject = "Reset password mail from " . $this->user->business?($this->user->business->name . " HRM"):env("APP_NAME");
        return $this->subject($subject)->view('email.dynamic_mail', ["html_content" => $html_content]);











        // return $this->view('email.reset_password_mail', [
        //     "url" => ($front_end_url . '/auth/change-password?token=' . $this->user->resetPasswordToken)
        // ]);
    }
}
