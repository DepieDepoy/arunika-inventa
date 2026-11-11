<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'en' ? 'en' : 'id' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ __('Reset Password VASETRA') }}</title>
</head>

<body style="
    margin: 0;
    padding: 0;
    background-color: #f4f6f9;
    font-family: Arial, Helvetica, sans-serif;
    color: #333333;
">

    <!-- MAIN WRAPPER -->
    <table
        width="100%"
        cellpadding="0"
        cellspacing="0"
        border="0"
        style="background-color: #f4f6f9; padding: 40px 15px;"
    >

        <tr>
            <td align="center">

                <!-- EMAIL CONTAINER -->
                <table
                    width="600"
                    cellpadding="0"
                    cellspacing="0"
                    border="0"
                    style="
                        width: 100%;
                        max-width: 600px;
                        background-color: #ffffff;
                        border-radius: 12px;
                        overflow: hidden;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
                    "
                >

                    <!-- HEADER -->
                    <tr>
                        <td
                            align="center"
                            style="
                                padding: 30px 30px 25px;
                                background-color: #ffffff;
                                border-bottom: 1px solid #eeeeee;
                            "
                        >

                            <!-- VASETRA LOGO -->
                            <img
                                src="{{ asset('assets/images/auth/vasetra.png') }}"
                                alt="{{ __('VASETRA') }}"
                                style="
                                    display: block;
                                    max-width: 230px;
                                    width: 100%;
                                    height: auto;
                                    margin: 0 auto;
                                "
                            >

                        </td>
                    </tr>


                    <!-- CONTENT -->
                    <tr>
                        <td
                            style="
                                padding: 40px 40px 30px;
                            "
                        >

                            <!-- TITLE -->
                            <h1
                                style="
                                    margin: 0 0 20px;
                                    font-size: 24px;
                                    line-height: 1.4;
                                    color: #222222;
                                    font-weight: 700;
                                "
                            >
                                {{ __('Reset Password') }}
                            </h1>


                            <!-- GREETING -->
                            <p
                                style="
                                    margin: 0 0 15px;
                                    font-size: 15px;
                                    line-height: 1.7;
                                    color: #555555;
                                "
                            >
                                {{ __('Halo') }}
                                <strong>
                                    {{ $user->name ?? 'User' }}
                                </strong>,
                            </p>


                            <!-- MESSAGE -->
                            <p
                                style="
                                    margin: 0 0 18px;
                                    font-size: 15px;
                                    line-height: 1.7;
                                    color: #555555;
                                "
                            >
                                {{ __('Kami menerima permintaan untuk melakukan reset password pada akun VASETRA Anda.') }}
                            </p>


                            <p
                                style="
                                    margin: 0 0 25px;
                                    font-size: 15px;
                                    line-height: 1.7;
                                    color: #555555;
                                "
                            >
                                {{ __('Silakan klik tombol di bawah ini untuk membuat password baru:') }}
                            </p>


                            <!-- BUTTON -->
                            <table
                                width="100%"
                                cellpadding="0"
                                cellspacing="0"
                                border="0"
                            >
                                <tr>
                                    <td align="center">

                                        <a
                                            href="{{ $url }}"
                                            target="_blank"
                                            style="
                                                display: inline-block;
                                                padding: 14px 30px;
                                                background-color: #3a57e8;
                                                color: #ffffff;
                                                text-decoration: none;
                                                font-size: 15px;
                                                font-weight: 600;
                                                border-radius: 8px;
                                            "
                                        >
                                            {{ __('Reset Password') }}
                                        </a>

                                    </td>
                                </tr>
                            </table>


                            <!-- EXPIRATION -->
                            <p
                                style="
                                    margin: 28px 0 0;
                                    padding: 15px;
                                    background-color: #f8f9fa;
                                    border-radius: 8px;
                                    font-size: 13px;
                                    line-height: 1.6;
                                    color: #666666;
                                "
                            >
                                <strong>{{ __('Catatan:') }}</strong><br>
                                {{ __('Link reset password ini hanya berlaku selama :p1 menit.', ['p1' => config('auth.passwords.users.expire', 60)]) }}
                            </p>


                            <!-- SECURITY NOTICE -->
                            <p
                                style="
                                    margin: 25px 0 0;
                                    font-size: 13px;
                                    line-height: 1.7;
                                    color: #777777;
                                "
                            >
                                {{ __('Jika Anda tidak meminta reset password, Anda dapat mengabaikan email ini. Password akun Anda tidak akan berubah sampai Anda menggunakan link di atas.') }}
                            </p>


                            <!-- FALLBACK URL -->
                            <p
                                style="
                                    margin: 25px 0 0;
                                    font-size: 12px;
                                    line-height: 1.6;
                                    color: #999999;
                                    word-break: break-all;
                                "
                            >
                                {{ __('Jika tombol di atas tidak dapat diklik, salin dan buka link berikut pada browser:') }}
                            </p>

                            <p
                                style="
                                    margin: 8px 0 0;
                                    font-size: 12px;
                                    line-height: 1.6;
                                    word-break: break-all;
                                "
                            >
                                <a
                                    href="{{ $url }}"
                                    target="_blank"
                                    style="
                                        color: #3a57e8;
                                        text-decoration: none;
                                    "
                                >
                                    {{ $url }}
                                </a>
                            </p>

                        </td>
                    </tr>


                    <!-- FOOTER -->
                    <tr>
                        <td
                            align="center"
                            style="
                                padding: 25px 30px;
                                background-color: #f8f9fa;
                                border-top: 1px solid #eeeeee;
                            "
                        >

                            <p
                                style="
                                    margin: 0 0 6px;
                                    font-size: 13px;
                                    color: #777777;
                                "
                            >
                                {{ __('Email ini dikirim secara otomatis oleh') }}
                            </p>

                            <p
                                style="
                                    margin: 0;
                                    font-size: 14px;
                                    font-weight: 700;
                                    color: #333333;
                                "
                            >
                                {{ __('VASETRA') }}
                            </p>

                            <p
                                style="
                                    margin: 8px 0 0;
                                    font-size: 11px;
                                    color: #aaaaaa;
                                "
                            >
                                {{ __('Asset Management System') }}
                            </p>

                        </td>
                    </tr>

                </table>

                <!-- COPYRIGHT -->
                <table
                    width="600"
                    cellpadding="0"
                    cellspacing="0"
                    border="0"
                    style="
                        width: 100%;
                        max-width: 600px;
                    "
                >
                    <tr>
                        <td
                            align="center"
                            style="
                                padding: 20px 15px;
                                font-size: 11px;
                                color: #999999;
                            "
                        >
                            © {{ date('Y') }} VASETRA.
                            All rights reserved.
                        </td>
                    </tr>
                </table>

            </td>
        </tr>

    </table>

</body>
</html>