<?php

namespace App\Mail;

use App\Http\Utils\BasicEmailUtil;
use App\Models\Business;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendPasswordMail extends Mailable
{
    use Queueable, SerializesModels, BasicEmailUtil;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $user;
    public $password;
    public function __construct($user, $password)
    {
        $this->user = $user;
        $this->password = $password;
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
        } else {
        }


        $email_content = EmailTemplate::where([
            "type" => "send_password_mail",
            "is_active" => 1,
            "business_id" => $business_id,
            "is_default" => $is_default,

        ])->first();


        if (empty($email_content)) {
            $email_content = $this->storeEmailTemplateIfNotExists("send_password_mail", $business_id, $is_default, TRUE);
        }

        $front_end_url = env('FRONT_END_URL');
        $password_reset_link =  ($front_end_url . '/auth/change-password?token=' . $this->user->resetPasswordToken);


        $html_content = $email_content->template;
        $html_content =  str_replace("[FULL_NAME]", ($this->user->title . " " . $this->user->first_Name . " " . $this->user->middle_Name . " " . $this->user->last_Name . " "), $html_content);
        $html_content =  str_replace("[APP_NAME]", env('APP_NAME'), $html_content);
        $html_content =  str_replace("[PASSWORD]", $this->password, $html_content);
        $html_content =  str_replace("[PASSWORD_RESET_LINK]", $password_reset_link, $html_content);
        $html_content =  str_replace("[ACCOUNT_VERIFICATION_LINK]", (env('APP_URL') . '/activate/' . $this->user->email_verify_token), $html_content);




        $subject = "Your Password from " . ($this->user->business ? ($this->user->business->name . " HRM") : env("APP_NAME"));
        return $this->subject($subject)->view('email.dynamic_mail', ["html_content" => $html_content]);
    }
}
