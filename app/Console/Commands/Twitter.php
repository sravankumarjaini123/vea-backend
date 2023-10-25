<?php

namespace App\Console\Commands;

use App\Models\Twitters;
use App\Models\User;
use App\Models\UsersTwitters;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Twitter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitter:refreshToken';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Artisan command to check for expiry of access token and gets the refresh token';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            if (Schema::hasTable('users_twitters')) {
                $client = new Client();
                $users_twitters = DB::table('users_twitters')->get();
                if (!empty($users_twitters)) {
                    foreach ($users_twitters as $users_twitter) {
                        $user_details = UsersTwitters::where('id', $users_twitter->id)
                            ->where('auth_type', 'new')->where('auth_id', null)
                            ->first();
                        if (!empty($user_details)) {
                            $user = User::where('id', $user_details->users_id)->first();
                            $twitter = Twitters::where('id', $user_details->twitters_id)->first();
                            $basic_token = base64_encode($twitter->client_id . ':' . $twitter->client_secret);
                            $now = Carbon::now()->diffInMinutes($user_details->created_at);
                            if ($now >= 75) {
                                $headers = [
                                    'Content-Type' => 'application/x-www-form-urlencoded',
                                    'Authorization' => 'Basic ' . $basic_token,
                                ];
                                $refresh = $client->request('POST',
                                    'https://api.twitter.com/2/oauth2/token?grant_type=refresh_token&refresh_token=' . $user_details->refresh_token,
                                    ['headers' => $headers]);
                                $refresh_body = json_decode($refresh->getBody());
                                $additional_values = [
                                    'access_token' => $refresh_body->access_token,
                                    'refresh_token' => $refresh_body->refresh_token,
                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                ];
                                UsersTwitters::where('id', $user_details->id)->update($additional_values);
                                $child_twitters = UsersTwitters::where('auth_id', $user_details->id)->get();
                                if (!empty($child_twitters)) {
                                    foreach ($child_twitters as $child_twitter) {
                                        UsersTwitters::where('id', $child_twitter->id)->update($additional_values);
                                    }
                                }
                            }
                        }
                    }
                }
                return 1;
            } else {
                return 0;
            }
        } catch (GuzzleException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function
}
