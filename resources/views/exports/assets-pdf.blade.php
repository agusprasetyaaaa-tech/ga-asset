<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Data Aset</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 8px; color: #1f2937; }
        .header { background: #059669; color: white; padding: 20px 25px; margin-bottom: 0; }
        .header h1 { font-size: 18px; font-weight: bold; margin-bottom: 3px; }
        .header p { font-size: 9px; opacity: 0.85; }
        .meta-bar { background: #f0fdf4; border-bottom: 2px solid #059669; padding: 10px 25px; display: flex; }
        .meta-item { display: inline-block; margin-right: 30px; }
        .meta-label { font-size: 7px; color: #6b7280; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px; }
        .meta-value { font-size: 11px; font-weight: bold; color: #065f46; }
        .content { padding: 15px 25px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead th {
            background: #059669; color: white; padding: 6px 5px; text-align: left;
            font-size: 7px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px;
            border: 1px solid #047857;
        }
        tbody td {
            padding: 5px 5px; border: 1px solid #e5e7eb; font-size: 7.5px; vertical-align: top;
        }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody tr:hover { background: #f0fdf4; }
        .status-badge {
            display: inline-block; padding: 2px 6px; border-radius: 3px;
            font-size: 6.5px; font-weight: bold; text-transform: uppercase;
        }
        .status-available { background: #d1fae5; color: #065f46; }
        .status-in_use { background: #dbeafe; color: #1e40af; }
        .status-maintenance { background: #fef3c7; color: #92400e; }
        .status-damaged { background: #fee2e2; color: #991b1b; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-mono { font-family: 'DejaVu Sans Mono', monospace; }
        .footer { margin-top: 15px; padding-top: 10px; border-top: 1px solid #d1d5db; text-align: center; font-size: 7px; color: #9ca3af; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Data Aset</h1>
        <p>Asset Management System — Dicetak pada {{ $generatedAt }}</p>
    </div>

    <div class="meta-bar">
        <span class="meta-item">
            <span class="meta-label">Total Aset</span><br>
            <span class="meta-value">{{ number_format($totalAssets) }}</span>
        </span>
        <span class="meta-item">
            <span class="meta-label">Total Nilai Aset</span><br>
            <span class="meta-value">Rp {{ number_format($totalValue, 0, ',', '.') }}</span>
        </span>
    </div>

    <div class="content">
        <table>
            <thead>
                <tr>
                    <th style="width:20px" class="text-center">No</th>
                    <th style="width:70px">Kode Aset</th>
                    <th>Nama Aset</th>
                    <th style="width:55px">Merek</th>
                    <th style="width:65px">Serial Number</th>
                    <th style="width:55px">Kategori</th>
                    <th style="width:55px">Departemen</th>
                    <th style="width:50px">Lokasi</th>
                    <th style="width:40px" class="text-center">Status</th>
                    <th style="width:40px" class="text-center">Kondisi</th>
                    <th style="width:55px">Pemegang</th>
                    <th style="width:60px" class="text-right">Harga (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $statusMap = ['available' => 'Tersedia', 'in_use' => 'Digunakan', 'maintenance' => 'Perbaikan', 'damaged' => 'Rusak'];
                    $conditionMap = ['baik' => 'Baik', 'cukup_baik' => 'Cukup Baik', 'kurang_baik' => 'Kurang Baik', 'rusak' => 'Rusak'];
                @endphp
                @foreach($assets as $i => $asset)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td class="text-mono">{{ $asset->asset_code }}</td>
                    <td><strong>{{ $asset->name }}</strong></td>
                    <td>{{ $asset->brand ?? '-' }}</td>
                    <td class="text-mono" style="font-size:6.5px">{{ $asset->serial_number }}</td>
                    <td>{{ $asset->subcategory?->category?->name ?? '-' }}</td>
                    <td>{{ $asset->department_rel?->name ?? $asset->department ?? '-' }}</td>
                    <td>{{ $asset->location?->name ?? '-' }}</td>
                    <td class="text-center">
                        <span class="status-badge status-{{ $asset->status }}">
                            {{ $statusMap[$asset->status] ?? $asset->status }}
                        </span>
                    </td>
                    <td class="text-center" style="font-size:6.5px">{{ $conditionMap[$asset->condition] ?? $asset->condition }}</td>
                    <td>{{ $asset->current_holder ?? '-' }}</td>
                    <td class="text-right text-mono">{{ $asset->purchase_price ? number_format($asset->purchase_price, 0, ',', '.') : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh Asset Management System &bull; {{ $generatedAt }}
    </div>
</body>
</html>
