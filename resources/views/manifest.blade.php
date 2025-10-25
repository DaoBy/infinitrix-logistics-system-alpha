<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manifest: {{ $manifest->manifest_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.3;
            margin: 0;
            padding: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .document-title {
            font-size: 16px;
            margin-bottom: 10px;
        }
        .manifest-info {
            font-size: 11px;
            margin-top: 5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            border: 1px solid #000;
        }
        .info-box {
            flex: 1;
            padding: 8px;
            border-right: 1px solid #000;
        }
        .info-box:last-child {
            border-right: none;
        }
        .info-label {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background-color: #f5f5f5;
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
        }
        td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 10px;
        }
        .summary {
            margin-top: 15px;
            padding: 8px;
            border: 1px solid #000;
            background-color: #f9f9f9;
            font-size: 11px;
            text-align: center;
        }
        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #000;
        }
        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        .signature-box {
            flex: 1;
            text-align: center;
        }
        .signature-box:first-child {
            border-right: 1px solid #ddd;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 30px;
            padding-top: 5px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">INFINITRIX EXPRESS CARGO</div>
        <div class="document-title">TRUCK MANIFEST</div>
        <div class="manifest-info">
            <strong>{{ $manifest->manifest_number }}</strong> | {{ $currentDate }}
        </div>
    </div>

    <!-- Truck & Driver Info - Side by Side -->
    <div class="info-row">
        <div class="info-box">
            <div class="info-label">TRUCK</div>
            <div>{{ $manifest->truck->license_plate }} - {{ $manifest->truck->make }} {{ $manifest->truck->model }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">DRIVER</div>
            <div>
                {{ $manifest->driver->name ?? 'Not Assigned' }}
                @if($manifest->driver && $manifest->driver->employeeProfile)
                    (ID: {{ $manifest->driver->employeeProfile->employee_id }})
                @endif
            </div>
        </div>
    </div>

    <!-- Packages Table -->
    <table>
        <thead>
            <tr>
                <th>Item Code</th>
                <th>Category</th>
                <th>Package Name</th>
                <th>Destination</th>
                <th>Waybill #</th>
            </tr>
        </thead>
        <tbody>
            @foreach($packages as $package)
            <tr>
                <td style="font-family: 'Courier New', monospace; font-weight: bold;">{{ $package->item_code }}</td>
                <td>{{ $package->category }}</td>
                <td>{{ $package->item_name }}</td>
                <td>{{ $package->deliveryRequest->dropOffRegion->name ?? 'N/A' }}</td>
                <td style="font-weight: bold;">{{ $package->waybill->waybill_number ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Summary -->
    <div class="summary">
        <strong>Total Packages: {{ $packages->count() }}</strong> | 
        <strong>Total Volume: {{ number_format($packages->sum('volume'), 3) }} m³</strong> | 
        <strong>Total Weight: {{ number_format($packages->sum('weight'), 2) }} kg</strong>
    </div>

    <!-- Footer with Signatures Side by Side -->
    <div class="footer">
        <div class="signature-row">
            <div class="signature-box">
                <div class="signature-line">Driver's Signature</div>
                <div style="font-size: 9px; margin-top: 3px;">
                    Name: {{ $manifest->driver->name ?? 'N/A' }}<br>
                    Date: ________________
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Dispatcher's Signature</div>
                <div style="font-size: 9px; margin-top: 3px;">
                    Name: {{ $manifest->generator->name ?? 'N/A' }}<br>
                    Date: ________________
                </div>
            </div>
        </div>
        
        @if($manifest->notes)
        <div style="margin-top: 15px; font-size: 10px;">
            <strong>Notes:</strong> {{ $manifest->notes }}
        </div>
        @endif
    </div>
</body>
</html>