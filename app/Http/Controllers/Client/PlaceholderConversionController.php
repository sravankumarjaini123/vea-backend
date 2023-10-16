<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\NewslettersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use App\Models\User;

class PlaceholderConversionController extends Controller
{
    public function convertPlaceholders($description, $salutation, $firstname, $lastname, $email)
    {
        $locale = env('APP_LANGUAGE');
        if (str_contains($description, '{{%salutation_formal%}}')) {
            $salutation_message = Lang::get('messages.salutation_formal', [], $locale);
            $description = str_replace('{{%salutation_formal%}}', $salutation_message .
                ' ' . $salutation .
                ' ' . $firstname .
                ' ' . $lastname, $description);
        }
        if (str_contains($description, '{{%salutation_informal%}}')) {
            $salutation_message = Lang::get('messages.salutation_informal', [], $locale);
            $description = str_replace('{{%salutation_informal%}}', $salutation_message .
                ' ' . $salutation .
                ' ' . $firstname .
                ' ' . $lastname, $description);
        }

        if (str_contains($description, '{{%signature_formal%}}')) {
            $signature_message = Lang::get('messages.signature_formal', [], $locale);
            $description = str_replace('{{%signature_formal%}}', $signature_message, $description);
        }
        if (str_contains($description, '{{%signature_informal%}}')) {
            $signature_message = Lang::get('messages.signature_informal', [], $locale);
            $description = str_replace('{{%signature_informal%}}', $signature_message, $description);
        }
        // Replace First Name of the User if existing
        if (str_contains($description, '{{%first_name%}}')) {
            $description = str_replace('{{%first_name%}}', $firstname, $description);
        }
        // Replace Last Name of the User if existing
        if (str_contains($description, '{{%last_name%}}')) {
            $description = str_replace('{{%last_name%}}', $lastname, $description);
        }
        // Replace Email of the User if existing
        if (str_contains($description, '{{%email%}}')) {
            $description = str_replace('{{%email%}}', $email, $description);
        }

        return $description;
    } // End Function

    /**
     * Method allow to conver users related placeholders
     * @param $description
     * @param $user_id
     * @return array|mixed|string|string[]
     */
    public function convertPlaceholdersForUser($description, $user_id)
    {
        $user = User::where('id', $user_id)->first();
        return $this->convertPlaceholders($description, $user->salutation->salutation, $user->firstname, $user->lastname, $user->email);
    } // End Function

    /**
     * Method allow to convert newsletters related placeholder
     * @param $description
     * @param $newslettersuser_id
     * @return array|mixed|string|string[]
     */
    public function convertPlaceholdersForNewlettersUser($description, $newslettersuser_id): array
    {
        $newslettersuser = NewslettersUsers::where('id', $newslettersuser_id)->first();
        return $this->convertPlaceholders($description, $newslettersuser->salutations->salutation, $newslettersuser->firstname, $newslettersuser->lastname, $newslettersuser->email);
    } // End Function
}
