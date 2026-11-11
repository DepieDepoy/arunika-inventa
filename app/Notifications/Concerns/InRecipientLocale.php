<?php

namespace App\Notifications\Concerns;

use App\Http\Middleware\SetUserLocale;

/**
 * Email dikirim dalam bahasa PENERIMA (users.locale; bawaan Indonesia).
 *
 * Dipanggil di via(): Laravel memanggil via() sebelum mengirim / memasukkan
 * ke antrean, lalu merender email di dalam locale notifikasi ($this->locale).
 * Hanya dipakai notifikasi email; notifikasi lonceng & push tidak terpengaruh.
 */
trait InRecipientLocale
{
    protected function useRecipientLocale(object $notifiable): void
    {
        // Selalu ditetapkan ulang: satu objek notifikasi bisa dikirim ke banyak penerima.
        $this->locale = SetUserLocale::resolve($notifiable->locale ?? null);
    }
}
