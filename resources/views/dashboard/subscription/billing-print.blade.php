
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Billing {{ $payment->order_id }} - Vasetra</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #f1f5f9;
            color: #172033;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
        }

        .toolbar {
            max-width: 900px;
            margin: 24px auto 12px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            padding: 10px 16px;
            border: 0;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-primary { background: #1746a2; color: white; }
        .btn-light { background: white; color: #172033; }

        .invoice {
            max-width: 900px;
            margin: 0 auto 30px;
            padding: 42px;
            background: white;
            box-shadow: 0 5px 24px rgba(15, 23, 42, .08);
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 25px;
            border-bottom: 3px solid #1746a2;
        }

        .brand {
            font-size: 27px;
            font-weight: 800;
            color: #1746a2;
            letter-spacing: 1px;
        }

        .muted { color: #64748b; }
        .small { font-size: 12px; }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h1 {
            margin: 0 0 8px;
            font-size: 25px;
            letter-spacing: 1px;
        }

        .section {
            margin-top: 28px;
        }

        .section-title {
            margin: 0 0 12px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #64748b;
        }

        .parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
        }

        .party-name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
        }

        .meta td {
            padding: 7px 0;
            vertical-align: top;
        }

        .meta td:first-child {
            width: 42%;
            color: #64748b;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items th {
            background: #f1f5f9;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            color: #475569;
        }

        .items th, .items td {
            padding: 13px 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .text-right { text-align: right !important; }

        .total-box {
            width: 340px;
            max-width: 100%;
            margin: 20px 0 0 auto;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 9px 0;
        }

        .grand-total {
            margin-top: 5px;
            padding-top: 15px;
            border-top: 2px solid #172033;
            font-size: 19px;
            font-weight: 800;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 5px;
            background: #e2e8f0;
            font-size: 12px;
            font-weight: 700;
        }

        .notice {
            margin-top: 28px;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            line-height: 1.6;
        }

        .footer {
            margin-top: 35px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }

        @media print {
            @page { size: A4; margin: 12mm; }

            body { background: white; font-size: 12px; }
            .toolbar { display: none !important; }
            .invoice {
                max-width: none;
                margin: 0;
                padding: 10px;
                box-shadow: none;
            }
            .section { break-inside: avoid; }
        }

        @media (max-width: 600px) {
            .invoice { padding: 20px; }
            .header { flex-direction: column; }
            .invoice-title { text-align: left; }
            .parties { grid-template-columns: 1fr; }
            .items th, .items td { padding: 9px 5px; }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <button class="btn btn-primary" onclick="window.print()">
            Print / Save PDF
        </button>
        <button class="btn btn-light" onclick="window.close()">
            Tutup
        </button>
    </div>

    @php
        $money = fn ($value) => 'Rp ' . number_format(
            (float) ($value ?? 0),
            0,
            ',',
            '.'
        );

        $packagePrice = (float) ($payment->package_price ?? 0);
        $storagePrice = (float) ($payment->storage_addon_price ?? 0);
        $discount = (float) ($payment->discount_amount ?? 0);

        // Untuk transaksi lama, gross_amount menjadi sumber nominal utama.
        $grossAmount = (float) ($payment->gross_amount ?? (
            $packagePrice + $storagePrice - $discount
        ));

        $storageGb = (float) ($payment->storage_addon_gb ?? 0);

        $periodStart = $subscription?->start_date;
        $periodEnd = $subscription?->end_date;

        $paymentStatus = strtolower(
            (string) ($payment->transaction_status ?? 'unknown')
        );

        $statusLabels = [
            'success' => 'Berhasil',
            'paid' => 'Lunas',
            'settlement' => 'Lunas',
            'capture' => 'Berhasil',
            'pending' => 'Menunggu Pembayaran',
            'cancel' => 'Dibatalkan',
            'cancelled' => 'Dibatalkan',
            'expired' => 'Kedaluwarsa',
            'failure' => 'Gagal',
            'failed' => 'Gagal',
            'deny' => 'Ditolak',
        ];

        $paymentStatusLabel = $statusLabels[$paymentStatus]
            ?? ucfirst($paymentStatus);

        $cycle = $payment->billing_cycle
            ?? $subscription?->billing_cycle
            ?? '-';
    @endphp

    <main class="invoice">
        <header class="header">
            <div>
                <div class="brand">VASETRA</div>
                <div class="muted">Subscription Billing Statement</div>
                <div class="muted small">Dokumen tagihan berlangganan</div>
            </div>

            <div class="invoice-title">
                <h1>BILLING</h1>
                <div><strong>{{ $payment->order_id }}</strong></div>
                <div class="muted small">
                    Dicetak: {{ now()->format('d M Y H:i') }}
                </div>
            </div>
        </header>

        <section class="section parties">
            <div>
                <h2 class="section-title">Ditagihkan kepada</h2>
                <div class="party-name">
                    {{ $company?->company_name ?? '-' }}
                </div>
                @if($company?->email)
                    <div>{{ $company->email }}</div>
                @endif
                @if($company?->address)
                    <div class="muted">{{ $company->address }}</div>
                @endif
            </div>

            <div>
                <h2 class="section-title">Informasi billing</h2>
                <table class="meta">
                    <tr>
                        <td>Nomor invoice</td>
                        <td><strong>{{ $payment->order_id }}</strong></td>
                    </tr>
                    <tr>
                        <td>Paket</td>
                        <td>{{ $payment->plan_name ?? $plan?->plan_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Siklus billing</td>
                        <td>{{ ucfirst($cycle) }}</td>
                    </tr>
                    <tr>
                        <td>Status pembayaran</td>
                        <td><span class="status">{{ $paymentStatusLabel }}</span></td>
                    </tr>
                </table>
            </div>
        </section>

        <section class="section">
            <h2 class="section-title">Rincian tagihan</h2>

            <table class="items">
                <thead>
                    <tr>
                        <th>Deskripsi</th>
                        <th class="text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>
                                Subscription {{ $payment->plan_name ?? $plan?->plan_name ?? 'Paket' }}
                            </strong>
                            <div class="muted small">
                                Periode {{ ucfirst($cycle) }}
                            </div>
                        </td>
                        <td class="text-right">
                            {{ $money($packagePrice) }}
                        </td>
                    </tr>

                    @if($storageGb > 0 || $storagePrice > 0)
                        <tr>
                            <td>
                                Tambahan storage
                                <div class="muted small">
                                    {{ number_format($storageGb, 1, ',', '.') }} GB
                                </div>
                            </td>
                            <td class="text-right">
                                {{ $money($storagePrice) }}
                            </td>
                        </tr>
                    @endif

                    @if($discount > 0)
                        <tr>
                            <td>Diskon</td>
                            <td class="text-right">
                                -{{ $money($discount) }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <div class="total-box">
                <div class="total-row">
                    <span class="muted">Harga paket</span>
                    <strong>{{ $money($packagePrice) }}</strong>
                </div>

                @if($storagePrice > 0)
                    <div class="total-row">
                        <span class="muted">Tambahan storage</span>
                        <strong>{{ $money($storagePrice) }}</strong>
                    </div>
                @endif

                @if($discount > 0)
                    <div class="total-row">
                        <span class="muted">Diskon</span>
                        <strong>-{{ $money($discount) }}</strong>
                    </div>
                @endif

                <div class="total-row grand-total">
                    <span>Total tagihan</span>
                    <span>{{ $money($grossAmount) }}</span>
                </div>
            </div>
        </section>

        <section class="section">
            <h2 class="section-title">Periode subscription</h2>
            <table class="meta">
                <tr>
                    <td>Mulai</td>
                    <td>
                        {{ $periodStart ? \Carbon\Carbon::parse($periodStart)->format('d M Y') : '-' }}
                    </td>
                </tr>
                <tr>
                    <td>Berakhir</td>
                    <td>
                        {{ $periodEnd ? \Carbon\Carbon::parse($periodEnd)->format('d M Y') : '-' }}
                    </td>
                </tr>
                <tr>
                    <td>Status subscription</td>
                    <td>{{ ucfirst($subscription?->status ?? 'unknown') }}</td>
                </tr>
            </table>
        </section>

        <div class="notice">
            <strong>Catatan untuk klaim kantor</strong><br>
            Dokumen ini merupakan rincian billing subscription Vasetra.
            Untuk klaim biaya, lampirkan bukti pembayaran atau bukti settlement
            dari penyedia pembayaran apabila diwajibkan oleh kebijakan kantor.
            Status tagihan pada dokumen ini mengikuti catatan transaksi sistem.
        </div>

        <footer class="footer">
            Vasetra Subscription Billing · {{ $payment->order_id }}<br>
            Dokumen dicetak dari sistem. Simpan bersama bukti pembayaran untuk keperluan administrasi.
        </footer>
    </main>
</body>
</html>
