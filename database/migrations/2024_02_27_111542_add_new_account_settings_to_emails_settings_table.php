<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\EmailsSettings;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!EmailsSettings::where('technologies', 'app')->where('name', 'send_account_info')->exists()) {
            EmailsSettings::insert([
                'technologies' => 'app',
                'name' => 'send_account_info',
                'display_name' => 'Send Account Information',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails_settings', function (Blueprint $table) {
            //
        });
    }
};
