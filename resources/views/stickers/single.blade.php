<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Package Sticker - {{ $package['item_code'] }}</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
            size: 80mm 40mm; /* Standard shipping label size */
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            width: 80mm;
            height: 40mm;
            margin: 0;
            padding: 2mm;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 8px;
            line-height: 1.2;
            background: #ffffff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            display: flex;
            justify-content: center;
            align-items: center;
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
            background: {{ $package['destination_region']['color_hex'] ?? '#4F46E5' }};
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
        }
        
        .destination-text {
            font-size: 7px;
            color: white;
            opacity: 0.9;
            margin-right: 2px;
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
            height: 4.2mm;
        }
        
        .info-table td {
            padding: 0.4mm 0;
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
            color: #4F46E5;
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
        
        .company-brand {
            display: none; /* Consolidated into print-info */
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
        }
        
        @media print {
            body {
                margin: 0;
                padding: 2mm;
            }
        }
    </style>
</head>
<body>
    <div class="sticker">
        <div class="color-header">
            <div class="header-content">
                <span class="destination-text">DESTINATION:</span>
                <span class="region-name">{{ $package['destination_region']['name'] ?? 'MAIN HUB' }}</span>
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
                        <td class="label">Item:</td>
                        <td class="value">{{ Str::limit($package['item_name'], 25) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Receiver:</td>
                        <td class="value">{{ Str::limit($package['receiver']['name'] ?? 'N/A', 28) }}</td>
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
                Printed: {{ $printDate }} by {{ $printedBy }} | INFINITRIX EXPRESS
            </div>
        </div>
    </div>
</body>
</html>