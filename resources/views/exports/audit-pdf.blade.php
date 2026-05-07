<!DOCTYPE html>
<html>
<head>
    <title>Laporan Hasil Audit - {{ $audit->audit_no }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10pt; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #10b981; padding-bottom: 10px; }
        .header h2 { margin: 0; color: #059669; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; vertical-align: top; }
        .stats-box { background: #f0fdf4; border: 1px solid #bdf4d4; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .stats-table { width: 100%; text-align: center; }
        .stats-table th { color: #666; font-size: 8pt; text-transform: uppercase; }
        .stats-table td { font-size: 14pt; font-weight: bold; color: #059669; }
        .main-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .main-table th { background: #10b981; color: white; padding: 8px; font-size: 9pt; border: 1px solid #059669; }
        .main-table td { padding: 8px; border: 1px solid #ddd; font-size: 8pt; }
        .status-found { color: #059669; font-weight: bold; }
        .status-missing { color: #dc2626; font-weight: bold; }
        .status-mismatch { color: #d97706; font-weight: bold; }
        .footer { margin-top: 50px; text-align: right; }
        .signature { margin-top: 60px; display: inline-block; width: 200px; border-top: 1px solid #333; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN HASIL STOCK OPNAME</h2>
        <p>{{ $audit->audit_no }} | Lokasi: {{ $audit->location->name }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="20%"><strong>Auditor:</strong></td>
            <td>{{ $audit->user->name }}</td>
            <td width="20%"><strong>Status:</strong></td>
            <td>{{ strtoupper($audit->status) }}</td>
        </tr>
        <tr>
            <td><strong>Tanggal Mulai:</strong></td>
            <td>{{ $audit->started_at->format('d/m/Y H:i') }}</td>
            <td><strong>Selesai:</strong></td>
            <td>{{ $audit->completed_at ? $audit->completed_at->format('d/m/Y H:i') : '-' }}</td>
        </tr>
    </table>

    <div class="stats-box">
        <table class="stats-table">
            <tr>
                <th>Total Aset</th>
                <th>Ditemukan</th>
                <th>Hilang</th>
                <th>Salah Lokasi</th>
            </tr>
            <tr>
                <td>{{ $audit->total_assets }}</td>
                <td style="color: #059669;">{{ $audit->found_assets }}</td>
                <td style="color: #dc2626;">{{ $audit->missing_assets }}</td>
                <td style="color: #d97706;">{{ $audit->mismatch_assets }}</td>
            </tr>
        </table>
    </div>

    @if($audit->notes)
    <div style="margin-bottom: 20px;">
        <strong>Catatan:</strong><br>
        <p style="font-style: italic; background: #f9fafb; padding: 10px; border-radius: 5px;">{{ $audit->notes }}</p>
    </div>
    @endif

    <table class="main-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Kode Aset</th>
                <th>Nama Aset</th>
                <th width="15%">Status Audit</th>
                <th width="20%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($audit->items as $index => $item)
            <tr>
                <td align="center">{{ $index + 1 }}</td>
                <td align="center">{{ $item->asset->asset_code }}</td>
                <td>
                    <strong>{{ $item->asset->name }}</strong><br>
                    <small>{{ $item->asset->brand }} {{ $item->asset->model_type }}</small>
                </td>
                <td align="center">
                    <span class="status-{{ $item->status }}">
                        {{ strtoupper($item->status == 'mismatch' ? 'SALAH LOKASI' : $item->status) }}
                    </span>
                </td>
                <td>
                    @if($item->status == 'mismatch')
                        Seharusnya di: {{ $item->scanned_location }}
                    @endif
                    @if($item->scanned_at)
                        <br><small>Ditemukan pada: {{ $item->scanned_at->format('H:i') }}</small>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
        <div style="margin-top: 20px;">
            Auditor,<br><br><br><br>
            <div class="signature">({{ $audit->user->name }})</div>
        </div>
    </div>
</body>
</html>
