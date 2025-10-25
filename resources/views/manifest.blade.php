<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manifest: {{ $manifest->manifest_number }}</title>
    <style>
        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif; 
            margin: 0; 
            padding: 15px; 
            color: #000; 
            background: #fff; 
            font-size: 12px;
            line-height: 1.3;
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
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 15px; 
        }
        th, td { 
            border: 1px solid #000; 
            padding: 6px; 
            text-align: left; 
            font-size: 10px; 
            vertical-align: top;
        }
        th { 
            background: #f5f5f5; 
            font-weight: bold; 
        }
        .section-title { 
            font-weight: bold; 
            font-size: 11px; 
            margin-bottom: 8px; 
            background-color: #f5f5f5;
            padding: 6px;
        }
        .info-table { margin-bottom: 15px; }
        .footer { 
            margin-top: 20px; 
            padding-top: 15px; 
            border-top: 1px solid #000;
            font-size: 10px;
        }
        .label { font-weight: bold; }
        .text-center { text-align: center; }
        .signature-table { margin-top: 30px; }
        .signature-cell { 
            text-align: center; 
            padding-top: 40px; 
        }
        .signature-line {
            margin-top: 30px;
            padding-top: 5px;
            font-size: 10px;
        }
        .summary-cell {
            text-align: center;
            font-weight: bold;
            padding: 8px;
            background-color: #f9f9f9;
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

    <!-- Truck & Driver Information -->
    <table class="info-table">
        <tr>
            <th colspan="2" class="section-title">Vehicle & Driver Information</th>
        </tr>
        <tr>
            <td width="50%">
                <span class="label">TRUCK</span><br>
                <span class="label">License Plate:</span> {{ $manifest->truck->license_plate }}<br>
                <span class="label">Make/Model:</span> {{ $manifest->truck->make }} {{ $manifest->truck->model }}
            </td>
            <td width="50%">
                <span class="label">DRIVER</span><br>
                <span class="label">Name:</span> {{ $manifest->driver->name ?? 'Not Assigned' }}<br>
                <span class="label">Employee ID:</span> 
                @if($manifest->driver && $manifest->driver->employeeProfile)
                    {{ $manifest->driver->employeeProfile->employee_id }}
                @else
                    N/A
                @endif
            </td>
        </tr>
    </table>

    <!-- Packages Table -->
    <table class="info-table">
        <tr>
            <th colspan="5" class="section-title">Package Information</th>
        </tr>
        <tr>
            <th width="20%">Item Code</th>
            <th width="15%">Category</th>
            <th width="25%">Package Name</th>
            <th width="25%">Destination</th>
            <th width="15%">Waybill #</th>
        </tr>
        @foreach($packages as $package)
        <tr>
            <td style="font-family: 'Courier New', monospace; font-weight: bold;">{{ $package->item_code }}</td>
            <td>{{ $package->category }}</td>
            <td>{{ $package->item_name }}</td>
            <td>{{ $package->deliveryRequest->dropOffRegion->name ?? 'N/A' }}</td>
            <td style="font-weight: bold;">{{ $package->waybill->waybill_number ?? 'N/A' }}</td>
        </tr>
        @endforeach
    </table>

    <!-- Summary -->
    <table class="info-table">
        <tr>
            <th class="section-title">Summary</th>
        </tr>
        <tr>
            <td class="summary-cell">
                Total Packages: {{ $packages->count() }} | 
                Total Volume: {{ number_format($packages->sum('volume'), 3) }} m³ | 
                Total Weight: {{ number_format($packages->sum('weight'), 2) }} kg
            </td>
        </tr>
    </table>

    <!-- Signatures -->
    <table class="signature-table">
        <tr>
            <td width="50%" class="signature-cell">
                <div class="signature-line">Driver's Signature</div>
                <div style="font-size: 9px; margin-top: 3px;">
                    Name: {{ $manifest->driver->name ?? 'N/A' }}<br>
                    Date: ________________
                </div>
            </td>
            <td width="50%" class="signature-cell">
                <div class="signature-line">Dispatcher's Signature</div>
                <div style="font-size: 9px; margin-top: 3px;">
                    Name: {{ $manifest->generator->name ?? 'N/A' }}<br>
                    Date: ________________
                </div>
            </td>
        </tr>
    </table>

    @if($manifest->notes)
    <div class="footer">
        <div style="text-align: left;">
            <strong>Notes:</strong> {{ $manifest->notes }}
        </div>
    </div>
    @endif
</body>
</html>