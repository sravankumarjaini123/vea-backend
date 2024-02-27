<?php

namespace App\Jobs;

use App\Http\Controllers\Client\PlaceholderConversionController;
use App\Models\Emails;
use App\Models\EmailsSettings;
use App\Models\EmailsTemplates;
use App\Models\User;
use App\Models\UsersForgotPasswords;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendPasswordsAndRegistrationsMails implements ShouldQueue
{


    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $email_id;
    private $user_id;
    private $email_template_id;
    private $email_setting_id;
    private $code_id;
    private $condition;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($emails_id, $emails_templates_id, $users_id, $codes_id, $conditions, $emails_settings_id)
    {
        $this->email_id = $emails_id;
        $this->email_template_id = $emails_templates_id;
        $this->user_id = $users_id;
        $this->code_id = $codes_id;
        $this->condition = $conditions;
        $this->email_setting_id = $emails_settings_id;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $email_details = Emails::where('id', $this->email_id)->first();
        $email_template_details = EmailsTemplates::where('id', $this->email_template_id)->first();
        $emails_settings = EmailsSettings::where('id', $this->email_setting_id)->first();
        $user = User::where('id', $this->user_id)->first();
        if ($this->condition === 'system_forgot_password' || $this->condition === 'app_forgot_password') {
            $code = UsersForgotPasswords::where('id', $this->code_id)->first();
        }

        $password = Str::random(16);
        if ($this->condition === 'send_account_info') {
            $user->password = Hash::make($password);
            $user->save();
        }

        // Convert the General Placeholders
        $description = $email_template_details->description;
        $conversion = new PlaceholderConversionController();
        $description = $conversion->convertPlaceholdersForUser($description, $user->id);

        // Replace the Code for any Forgot password
        if( !empty($code) ) {
            $description = str_replace('{{%code%}}', $code->code, $description);
        }
        $subject = $emails_settings->subject;

        // Check the purpose and replace the link for redirection
        if ($this->condition === 'system_forgot_password'){
            $description = str_replace('{{%system_forgot_password_link%}}', $code->base64_email, $description);
        } elseif ($this->condition === 'app_forgot_password') {
            $description = str_replace('{{%app_forgot_password_link%}}', $code->base64_email, $description);
        } elseif ($this->condition === 'send_account_info') {
            $description = str_replace('{{%contact_password%}}', $password, $description);
        }

        // Configure the Mail system and Send the Mail
        config(['mail.mailers.smtp.host' => $email_details->host]);
        config(['mail.mailers.smtp.encryption' => $email_details->encryption]);
        config(['mail.mailers.smtp.username' => $email_details->username]);
        config(['mail.mailers.smtp.password' => $email_details->password]);
        config(['mail.mailers.smtp.port' => $email_details->port]);
        Mail::send([], [], function ($message) use ($user, $email_details, $description, $subject) {
            $message->to($user->email, $user->firstname)->subject($subject);
            $message->html($description, 'text/html');
            $message->from($email_details->senders_address, $email_details->senders_name);
        });
    }
}
