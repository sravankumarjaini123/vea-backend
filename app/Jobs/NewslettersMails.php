<?php

namespace App\Jobs;

use App\Http\Controllers\Client\PlaceholderConversionController;
use App\Mail\DoubleOptInMailable;
use App\Models\Emails;
use App\Models\EmailsSettings;
use App\Models\EmailsTemplates;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NewslettersMails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $email_id;
    private $user_id;
    private $email_template_id;
    private $email_setting_id;
    private $condition;
    private $newsletters_user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($emails_id, $emails_templates_id, $newsletters_user, $conditions, $emails_settings_id)
    {
        $this->email_id = $emails_id;
        $this->email_template_id = $emails_templates_id;
        $this->user_id = $newsletters_user->contacts_id;
        $this->condition = $conditions;
        $this->email_setting_id = $emails_settings_id;
        $this->newsletters_user = $newsletters_user;
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

        // Convert the General Placeholders
        $description = $email_template_details->description;
        $conversion = new PlaceholderConversionController();
        if(!empty($user)) {
            $description = $conversion->convertPlaceholdersForUser($description, $user->id);
            $hashed_email = base64_encode($user->email);
        } else {
            $description = $conversion->convertPlaceholdersForNewlettersUser($description, $this->newsletters_user->id);
            $hashed_email = $this->newsletters_user->hashed_user_email;
        }
        $subject = $emails_settings->subject;

        // Check the purpose and replace the link for redirection
        if( $this->condition == 'newsletters_activation_link') {
            $description = str_replace(['{{%system_newsletters_activation_link%}}', '{{%app_newsletters_activation_link%}}'], $hashed_email, $description);
        }

        // Configure the Mail system and Send the Mail
        config(['mail.mailers.smtp.host' => $email_details->host]);
        config(['mail.mailers.smtp.encryption' => $email_details->encryption]);
        config(['mail.mailers.smtp.username' => $email_details->username]);
        config(['mail.mailers.smtp.password' => $email_details->password]);
        config(['mail.mailers.smtp.port' => $email_details->port]);


        if(!empty($user)) {
            Mail::to($user['email'])->send(new DoubleOptInMailable($description, $subject));
        } else {
            Mail::to($this->newsletters_user->email)->send(new DoubleOptInMailable($description, $subject));
        }

    }
}
