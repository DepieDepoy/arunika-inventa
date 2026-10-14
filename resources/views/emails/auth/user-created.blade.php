<!DOCTYPE html>

<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun VASETRA Anda</title>
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
                        Akun VASETRA Anda
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
                        Administrator telah membuatkan akun
                        <strong>VASETRA</strong> untuk Anda.
                    </p>

                    <p style="
                        font-size:15px;
                        line-height:1.7;
                        margin:0 0 20px;
                    ">
                        Berikut informasi login sementara Anda:
                    </p>

                    {{-- LOGIN INFORMATION --}}
                    <table width="100%"
                           cellpadding="0"
                           cellspacing="0"
                           border="0"
                           style="
                               background:#f8f9fc;
                               border-radius:8px;
                               margin:0 0 25px;
                           ">

                        <tr>
                            <td style="
                                padding:18px 20px;
                                font-size:14px;
                                line-height:1.7;
                            ">

                                <div style="margin-bottom:10px;">
                                    <strong>Email</strong><br>
                                    <span style="color:#555555;">
                                        {{ $user->email }}
                                    </span>
                                </div>

                                <div>
                                    <strong>Password Sementara</strong><br>

                                    <span style="
                                        display:inline-block;
                                        background:#ffffff;
                                        border:1px solid #dddddd;
                                        border-radius:6px;
                                        padding:8px 12px;
                                        margin-top:4px;
                                        font-family:monospace;
                                        font-size:15px;
                                        color:#333333;
                                    ">
                                        {{ $password }}
                                    </span>
                                </div>

                            </td>
                        </tr>

                    </table>

                    <p style="
                        font-size:15px;
                        line-height:1.7;
                        margin:0 0 25px;
                    ">
                        Untuk mengaktifkan akses login Anda, silakan
                        verifikasi alamat email dengan menekan tombol
                        di bawah ini.
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

                    {{-- SECURITY NOTE --}}
                    <div style="
                        background:#fff8e1;
                        border-left:4px solid #ffc107;
                        padding:12px 15px;
                        margin-top:20px;
                        border-radius:4px;
                    ">

                        <p style="
                            font-size:13px;
                            line-height:1.6;
                            color:#6b5b00;
                            margin:0;
                        ">
                            <strong>Catatan keamanan:</strong><br>
                            Password di atas adalah password sementara.
                            Setelah berhasil login, segera ubah password
                            Anda untuk menjaga keamanan akun.
                        </p>

                    </div>

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
