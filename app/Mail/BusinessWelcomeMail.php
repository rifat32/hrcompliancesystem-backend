<?php

namespace App\Mail;

use App\Http\Utils\BasicEmailUtil;

use App\Models\EmailTemplate;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BusinessWelcomeMail extends Mailable
{
    use Queueable, SerializesModels, BasicEmailUtil;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $user;
    public $password;
    public function __construct($user,$password)
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
        $front_end_url = env('FRONT_END_URL');
        $password_reset_link =  ($front_end_url.'/auth/change-password?token='.$this->user->resetPasswordToken);


        $email_content = EmailTemplate::where([
            "type" => "business_welcome_mail",
            "is_active" => 1

        ])->first();



            if (empty($email_content)) {
                $email_content = $this->storeEmailTemplateIfNotExists("business_welcome_mail",NULL,0,FALSE);
               }





        $html_content = $email_content->template;
        $html_content =  str_replace("[FULL_NAME]", ($this->user->title . " " .$this->user->first_Name . " " . $this->user->middle_Name . " " . $this->user->last_Name . " "), $html_content );
        $html_content =  str_replace("[APP_NAME]", env("APP_NAME"), $html_content );
        $html_content =  str_replace("[PASSWORD_RESET_LINK]", $password_reset_link, $html_content );



        $subject = "Welcome from " . ($this->user->business?($this->user->business->name . " HRM"):env("APP_NAME"));
        return $this->subject($subject)->view('email.dynamic_mail', ["html_content" => $html_content]);






        // return $this->subject(("Welcome to " . env("APP_NAME") .  " - Set Your Password"))->view('email.business-welcome-mail',["user" => $this->user,"password_reset_link" => $password_reset_link]);


    }
}
