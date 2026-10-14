
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undangan Meeting VASETRA</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#f5f7fb;padding:40px 15px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                   style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;">

                {{-- HEADER --}}
                <tr>
                    <td align="center" style="padding:35px 30px 20px;">
                        <img src="{{ asset('assets/images/auth/vasetra.png') }}"
                             alt="VASETRA"
                             style="max-width:240px;width:100%;height:auto;">
                    </td>
                </tr>

                {{-- CONTENT --}}
                <tr>
                    <td style="padding:10px 40px 40px;color:#333333;">
                        <h2 style="margin:0 0 20px;font-size:24px;text-align:center;">
                            Undangan Meeting
                        </h2>

                        <p style="font-size:15px;line-height:1.7;margin:0 0 15px;">
                            Halo <strong>{{ $recipient->name }}</strong>,
                        </p>

                        <p style="font-size:15px;line-height:1.7;margin:0 0 20px;">
                            Anda diundang untuk mengikuti meeting melalui
                            <strong>VASETRA</strong>.
                            Berikut detail meeting yang perlu Anda ketahui.
                        </p>

                        {{-- MEETING DETAILS --}}
                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                               style="background:#f8f9fc;border-radius:8px;margin:0 0 25px;">
                            <tr>
                                <td style="padding:20px;font-size:14px;line-height:1.8;">

                                    <div style="margin-bottom:12px;">
                                        <strong>Judul Meeting</strong><br>
                                        <span style="color:#555555;">
                                            {{ $event->title }}
                                        </span>
                                    </div>

                                    <div style="margin-bottom:12px;">
                                        <strong>Penyelenggara</strong><br>
                                        <span style="color:#555555;">
                                            {{ $organizer->name }}
                                        </span>
                                    </div>

                                    <div style="margin-bottom:12px;">
                                        <strong>Mulai</strong><br>
                                        <span style="color:#555555;">
                                            {{ \Carbon\Carbon::parse($event->start_at)->format('d/m/Y H:i') }}
                                        </span>
                                    </div>

                                    <div style="margin-bottom:12px;">
                                        <strong>Selesai</strong><br>
                                        <span style="color:#555555;">
                                            {{ \Carbon\Carbon::parse($event->end_at)->format('d/m/Y H:i') }}
                                        </span>
                                    </div>

                                    @if(!empty($event->location))
                                        <div style="margin-bottom:12px;">
                                            <strong>Lokasi</strong><br>
                                            <span style="color:#555555;">
                                                {{ $event->location }}
                                            </span>
                                        </div>
                                    @endif

                                    @if(!empty($event->description))
                                        <div>
                                            <strong>Deskripsi</strong><br>
                                            <span style="color:#555555;white-space:pre-line;">{{ $event->description }}</span>
                                        </div>
                                    @endif

                                </td>
                            </tr>
                        </table>

                        <p style="font-size:15px;line-height:1.7;margin:0 0 20px;">
                            Mohon perhatikan jadwal tersebut dan pastikan Anda
                            dapat mengikuti meeting sesuai waktu yang ditentukan.
                        </p>

                        <div style="background:#eef3ff;border-left:4px solid #3a57e8;
                                    padding:12px 15px;margin-top:20px;border-radius:4px;">
                            <p style="font-size:13px;line-height:1.6;color:#334477;margin:0;">
                                <strong>Informasi:</strong><br>
                                Email ini merupakan notifikasi undangan meeting
                                otomatis dari VASETRA. Simpan informasi jadwal ini
                                sebagai pengingat.
                            </p>
                        </div>

                        <hr style="border:0;border-top:1px solid #eeeeee;margin:30px 0;">

                        <p style="font-size:12px;line-height:1.6;color:#999999;margin:0;">
                            Email ini dikirim secara otomatis oleh sistem VASETRA.
                            Jika terdapat pertanyaan mengenai meeting, silakan
                            hubungi penyelenggara.
                        </p>
                    </td>
                </tr>

                {{-- FOOTER --}}
                <tr>
                    <td align="center" style="background:#f8f9fc;padding:20px;">
                        <p style="margin:0;font-size:12px;color:#999999;">VASETRA</p>
                        <p style="margin:5px 0 0;font-size:11px;color:#aaaaaa;">
                            Asset Lifecycle Platform
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>