<!DOCTYPE html>
<html>
<head>
    <title>Manifest - {{ $manifest->manifest_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: bold; }
        .subtitle { font-size: 18px; margin-bottom: 10px; }
        .info-section { margin-bottom: 15px; }
        .info-label { font-weight: bold; }
        .section-title { font-weight: bold; margin-bottom: 5px; border-bottom: 1px solid #000; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .table th, .table td { border: 1px solid #000; padding: 5px; }
        .table th { background-color: #f2f2f2; }
        .signature-block { width: 45%; }
        .signature-line { width: 100%; border-bottom: 1px solid #000; height: 40px; margin-top: 30px; }
        .footer { margin-top: 30px; text-align: center; font-style: italic; }
        .flex { display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">INFINITRIX EXPRESS CARGO</div>
        <div class="subtitle">TRUCK MANIFEST</div>
    </div>

    <div class="info-section flex">
        <div>
            <div><span class="info-label">Date:</span> {{ $currentDate }}</div>
            <div><span class="info-label">Manifest #:</span> {{ $manifest->manifest_number }}</div>
            <div>
                <span class="info-label">Status:</span>
                {{ ucfirst($manifest->status) }}
            </div>
        </div>
        <div>
            <div class="info-label">Truck</div>
            <div>
                {{ $manifest->truck->make ?? '' }} {{ $manifest->truck->model ?? '' }}
                ({{ $manifest->truck->license_plate ?? '' }})
            </div>
            <div class="info-label" style="margin-top:10px;">Driver</div>
            <div>
                {{ $manifest->driver->name ?? 'Not assigned' }}
                (ID: {{ $manifest->driver->employeeProfile->employee_id ?? 'N/A' }})
            </div>
        </div>
    </div>

    <div class="section-title">Packages</div>
    @if($packages->count() > 0)
    <table class="table">
        <thead>
            <tr>
                <th>DO #</th>
                <th>Category</th>
                <th>Package Name</th>
                <th>Municipality</th>
                <th>Waybill #</th>
            </tr>
        </thead>
        <tbody>
            @foreach($packages as $pkg)
                <tr>
                    <td>DO-{{ str_pad($pkg->delivery_order_id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $pkg->category }}</td>
                    <td>{{ $pkg->item_name }}</td>
                    <td>{{ $pkg->drop_off_region ?? 'N/A' }}</td>
                    <td>{{ $pkg->waybill_number ?? 'Pending' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @else
        <div>No packages assigned to this truck</div>
    @endif

    <div class="flex" style="margin-top: 50px;">
        <div class="signature-block">
            <div class="info-label">Driver's Signature</div>
            <div class="signature-line"></div>
        </div>
        <div class="signature-block" style="text-align: right;">
            <div class="info-label">Dispatcher's Signature</div>
            <div class="signature-line"></div>
        </div>
    </div>
</body>
</html>