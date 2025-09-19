<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bulk Package Stickers - {{ now()->format('Y-m-d H:i') }}</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
            size: A4 portrait;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .page {
            width: 210mm;
            height: 297mm;
            padding: 5mm;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(5, 1fr);
            gap: 0mm;
            page-break-after: always;
        }
        
        .sticker-container {
            width: 100mm;
            height: 54mm;
            padding: 1mm;
            display: flex;
            align-items: center;
            justify-content: center;
            page-break-inside: avoid;
        }
        
        .sticker {
            width: 76mm;
            height: 36mm;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 2mm;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        
        .color-header {
            height: 5mm;
            background: {{ $package['destination_region']['color_hex'] ?? '#9bdbafff' }};
            margin: -2mm -2mm 2mm -2mm;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            position: relative;
        }
        
        .destination-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            text-align: center;
            position: absolute;
            left: 0;
            right: 0;
        }
        
        .destination-text {
            color: white;
            font-weight: bold;
            font-size: 7px;
            opacity: 0.9;
            margin-bottom: 1px;
        }
        
        .region-name {
            color: white;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }
        
        .sticker-content {
            display: table;
            width: 100%;
            height: calc(36mm - 12mm);
            border-collapse: collapse;
        }
        
        .package-info {
            display: table-cell;
            vertical-align: top;
            width: 70%;
            padding-right: 1.5mm;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-table tr {
            height: 3.8mm;
        }
        
        .info-table td {
            padding: 0.3mm 0;
            vertical-align: top;
        }
        
        .label {
            font-weight: 600;
            color: #6b7280;
            font-size: 7px;
            white-space: nowrap;
            width: 18mm;
            padding-right: 1mm;
        }
        
        .value {
            font-weight: 500;
            color: #1f2937;
            font-size: 7px;
            word-break: break-word;
        }
        
        .package-id-value {
            font-weight: 700;
            color: #2f1644ff;
        }
        
        .service-type-prepaid {
            color: #059669;
            font-weight: 700;
        }
        
        .service-type-postpaid {
            color: #dc2626;
            font-weight: 700;
        }
        
        .qr-section {
            display: table-cell;
            vertical-align: middle;
            width: 30%;
            text-align: center;
            padding-left: 1.5mm;
            border-left: 1px solid #f3f4f6;
        }
        
        .qr-container {
            width: 16mm;
            height: 16mm;
            border: 1px solid #e5e7eb;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9fafb;
            margin: 0 auto;
        }
        
        .qr-placeholder {
            text-align: center;
            color: #9ca3af;
            font-size: 6px;
            font-weight: 500;
            padding: 1mm;
        }
        
        .package-id {
            font-weight: 700;
            color: #374151;
            font-size: 7px;
            text-align: center;
            margin-top: 1mm;
            letter-spacing: 0.5px;
        }
        
        .footer {
            position: absolute;
            bottom: 2mm;
            right: 2mm;
            left: 2mm;
            display: flex;
            justify-content: center;
            align-items: center;
            padding-top: 1mm;
            border-top: 1px solid #f3f4f6;
        }
        
        .print-info {
            font-size: 6px;
            color: #9ca3af;
            text-align: center;
        }
        
        .urgent-badge {
            position: absolute;
            top: -1mm;
            right: 2mm;
            background: #ef4444;
            color: white;
            padding: 1px 3px;
            border-radius: 2px;
            font-size: 6px;
            font-weight: bold;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            z-index: 10;
        }
        
        .page-header {
            grid-column: 1 / -1;
            text-align: center;
            padding: 2mm;
            background: #ffffff;
            color: #2f1644ff;
            margin-bottom: 2mm;
            height: auto;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            margin-left: -1mm; /* Compensate for grid column spanning */
            margin-right: -1mm; /* Compensate for grid column spanning */
            width: calc(100% + 2mm); /* Adjust width to account for margins */
        }
        
        .page-header h1 {
            font-size: 14px;
            margin: 0;
            font-weight: bold;
            color: #2f1644ff;
        }
        
        .page-header p {
            font-size: 10px;
            margin: 1mm 0 0 0;
            color: #6b7280;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .page {
                padding: 5mm;
            }
            
            .page-header {
                margin-left: 0;
                margin-right: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    @php
        $perPage = 10;
        $totalPages = ceil(count($packages) / $perPage);
    @endphp

    @for($page = 0; $page < $totalPages; $page++)
        <div class="page">
            <div class="page-header">
                <h1>INFINITRIX EXPRESS - PACKAGE STICKERS</h1>
                <p>Page {{ $page + 1 }} of {{ $totalPages }} | Generated: {{ $printDate }}</p>
            </div>
            
            @for($i = 0; $i < $perPage; $i++)
                @php
                    $index = ($page * $perPage) + $i;
                    $package = $packages[$index] ?? null;
                @endphp
                
                @if($package)
                <div class="sticker-container">
                    <div class="sticker">
                        <div class="color-header" style="background: {{ $package['destination_region']['color_hex'] ?? '#b5dab5ff' }};">
                            <div class="header-content">
                                <div class="destination-container">
                                    <span class="destination-text">DESTINATION</span>
                                    <span class="region-name">{{ $package['destination_region']['name'] ?? 'MAIN HUB' }}</span>
                                </div>
                            </div>
                            @if(($package['weight'] ?? 0) > 20)
                            <div class="urgent-badge">HEAVY</div>
                            @endif
                        </div>
                        
                        <div class="sticker-content">
                            <div class="package-info">
                                <table class="info-table">
                                    <tr>
                                        <td class="label">Package ID:</td>
                                        <td class="value package-id-value">{{ $package['item_code'] }}</td>
                                    </tr>
                                    <tr>
                                        <td class="label">Service Type:</td>
                                        <td class="value {{ $package['delivery_request']['payment_type'] === 'prepaid' ? 'service-type-prepaid' : 'service-type-postpaid' }}">
                                            {{ strtoupper($package['delivery_request']['payment_type'] ?? 'N/A') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label">Item:</td>
                                        <td class="value">{{ Str::limit($package['item_name'], 22) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="label">Receiver:</td>
                                        <td class="value">{{ Str::limit($package['receiver']['name'] ?? 'N/A', 24) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="label">Waybill #:</td>
                                        <td class="value">{{ $package['waybill']['waybill_number'] ?? 'PENDING' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="label">Weight:</td>
                                        <td class="value">{{ $package['weight'] ?? 'N/A' }} kg</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="qr-section">
                                <div class="qr-container">
                                    <div class="qr-placeholder">
                                        QR CODE
                                    </div>
                                </div>
                                <div class="package-id">{{ $package['item_code'] }}</div>
                            </div>
                        </div>
                        
                        <div class="footer">
                            <div class="print-info">
                                Printed: {{ $printDate }} | INFINITRIX EXPRESS
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="sticker-container">
                    <!-- Empty slot -->
                </div>
                @endif
            @endfor
        </div>
    @endfor
</body>
</html>