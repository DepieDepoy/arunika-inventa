<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Verifikasi Email VASETRA</title>
</head>

<body style="
    margin:0;
    padding:0;
    background:#f5f7fb;
    font-family:Arial, Helvetica, sans-serif;
">

<table width="100%"
       cellpadding="0"
       cellspacing="0"
       border="0"
       style="background:#f5f7fb; padding:40px 15px;">

    <tr>
        <td align="center">

            <table width="100%"
                   cellpadding="0"
                   cellspacing="0"
                   border="0"
                   style="
                       max-width:600px;
                       background:#ffffff;
                       border-radius:12px;
                       overflow:hidden;
                   ">

                {{-- HEADER --}}
                <tr>
                    <td align="center"
                        style="padding:35px 30px 20px;">

                        <img
                            src="{{ asset('assets/images/auth/vasetra.png') }}"
                            alt="VASETRA"
                            style="
                                max-width:240px;
                                width:100%;
                                height:auto;
                            "
                        >

                    </td>
                </tr>

                {{-- CONTENT --}}
                <tr>
                    <td style="
                        padding:10px 40px 40px;
                        color:#333333;
                    ">

                        <h2 style="
                            margin:0 0 15px;
                            font-size:24px;
                            text-align:center;
                        ">
                            Verifikasi Email Anda
                        </h2>

                        <p style="
                            font-size:15px;
                            line-height:1.7;
                            margin:0 0 15px;
                        ">
                            Halo
                            <strong>{{ $user->name }}</strong>,
                        </p>

                        <p style="
                            font-size:15px;
                            line-height:1.7;
                            margin:0 0 20px;
                        ">
                            Terima kasih telah mendaftarkan akun
                            <strong>VASETRA</strong>.
                        </p>

                        <p style="
                            font-size:15px;
                            line-height:1.7;
                            margin:0 0 25px;
                        ">
                            Untuk mengaktifkan akses login Anda,
                            silakan verifikasi alamat email dengan
                            menekan tombol di bawah ini.
                        </p>

                        {{-- BUTTON --}}
                        <table width="100%"
                               cellpadding="0"
                               cellspacing="0"
                               border="0">

                            <tr>
                                <td align="center">

                                    <a href="{{ $url }}"
                                       style="
                                           display:inline-block;
                                           background:#3a57e8;
                                           color:#ffffff;
                                           text-decoration:none;
                                           padding:13px 28px;
                                           border-radius:7px;
                                           font-size:15px;
                                           font-weight:bold;
                                       ">
                                        VERIFIKASI EMAIL
                                    </a>

                                </td>
                            </tr>

                        </table>

                        <p style="
                            font-size:13px;
                            line-height:1.6;
                            color:#777777;
                            margin:25px 0 0;
                        ">
                            Link verifikasi ini berlaku selama
                            {{ config('auth.verification.expire', 60) }}
                            menit.
                        </p>

                        <p style="
                            font-size:13px;
                            line-height:1.6;
                            color:#777777;
                            margin:15px 0 0;
                        ">
                            Jika Anda tidak merasa melakukan
                            pendaftaran akun VASETRA, silakan
                            abaikan email ini.
                        </p>

                        <hr style="
                            border:0;
                            border-top:1px solid #eeeeee;
                            margin:30px 0;
                        ">

                        <p style="
                            font-size:12px;
                            line-height:1.6;
                            color:#999999;
                            margin:0;
                        ">
                            Jika tombol di atas tidak dapat diklik,
                            silakan buka link berikut pada browser:
                        </p>

                        <p style="
                            font-size:12px;
                            line-height:1.6;
                            word-break:break-all;
                            color:#3a57e8;
                            margin:8px 0 0;
                        ">
                            {{ $url }}
                        </p>

                    </td>
                </tr>

                {{-- FOOTER --}}
                <tr>
                    <td align="center"
                        style="
                            background:#f8f9fc;
                            padding:20px;
                        ">

                        <p style="
                            margin:0;
                            font-size:12px;
                            color:#999999;
                        ">
                            VASETRA
                        </p>

                        <p style="
                            margin:5px 0 0;
                            font-size:11px;
                            color:#aaaaaa;
                        ">
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