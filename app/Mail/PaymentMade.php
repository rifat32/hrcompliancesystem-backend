<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentMade extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $paymentDetails;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $paymentDetails)
    {
        $this->user = $user;
        $this->paymentDetails = $paymentDetails;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $business = $this->user->business;

        return $this
            ->subject('Payment Confirmation')
            ->view('email.payment_made', [
                'userName' => $this->user->title . " " .$this->user->first_Name . ' ' . $this->user->middle_Name . ' ' . $this->user->last_Name,
                'businessName' => $business->name,
                'servicePlanName' => $this->paymentDetails['service_plan_name'],
                'amount' => $this->paymentDetails['amount'],
                'transactionId' => $this->paymentDetails['transaction_id'],
                'paymentDate' => $this->paymentDetails['date'],
                'paymentMethod' => $this->paymentDetails['method'],
                'subscriptionStartDate' => $this->paymentDetails['subscription_start_date'],
                'subscriptionEndDate' => $this->paymentDetails['subscription_end_date'],
                'discountCode' => $this->paymentDetails['discountCode'],
                'discountAmount' => $this->paymentDetails['discountAmount'],
            ]);
    }
}
