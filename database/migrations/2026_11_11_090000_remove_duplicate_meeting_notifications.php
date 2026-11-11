<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Notifikasi lonceng "Undangan Meeting" (CalendarMeetingInvitation) adalah duplikat
 * dari "Undangan rapat" (GlobalNotification) dan URL-nya absolut sehingga 404.
 * Kini CalendarMeetingInvitation hanya mengirim email; baris lamanya dibersihkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('notifications')
            ->where('type', 'App\\Notifications\\CalendarMeetingInvitation')
            ->delete();
    }

    public function down(): void
    {
        // Data yang dihapus hanyalah duplikat; tidak dikembalikan.
    }
};
