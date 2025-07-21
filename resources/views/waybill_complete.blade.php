{{-- This is the complete/final waybill PDF template --}}
<!DOCTYPE html>
<html>
<head>
    <title>Waybill - {{ $waybill->waybill_number }} (Complete)</title>
    <style>
        body { font-family: 'Inter', Arial, sans-serif; margin: 0; padding: 32px; background: #f9fafb; color: #222; }
        .header { text-align: center; margin-bottom: 32px; }
        .header h1 { margin: 0; font-size: 32px; font-weight: bold; color: #111827; }
        .header p { margin: 8px 0 0; font-size: 20px; color: #6b7280; }
        .section { margin-bottom: 24px; }
        .section-title { font-weight: 600; font-size: 18px; color: #111827; margin-bottom: 8px; }
        .info-box { background: #f3f4f6; border-radius: 8px; padding: 16px; margin-bottom: 24px; }
        .row { display: flex; gap: 24px; }
        .col { flex: 1; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table, th, td { border: 1px solid #e5e7eb; }
        th, td { padding: 10px; text-align: left; font-size: 14px; }
        th { background: #f3f4f6; color: #374151; font-weight: 600; }
        .footer { margin-top: 40px; text-align: center; font-size: 13px; color: #6b7280; }
        .notice { font-size: 11px; color: #9ca3af; margin-top: 16px; }
        .flex { display: flex; gap: 32px; justify-content: center; align-items: center; }
        .label { font-weight: 600; color: #374151; }
        .value { color: #111827; }
        .border-t { border-top: 1px solid #e5e7eb; }
        .pt-4 { padding-top: 16px; }
        .text-xs { font-size: 12px; }
        .text-sm { font-size: 14px; }
        .text-center { text-align: center; }
        .rounded { border-radius: 8px; }
        .bg-gray { background: #f3f4f6; }
    </style>
</head>
<body>
    <div class="header">
        <h1>INFINITRIX EXPRESS CARGO</h1>
        <p>DELIVERY RECEIPT / WAYBILL (COMPLETED)</p>
    </div>

    <!-- Waybill Information -->
    <div class="row section">
        <div class="col">
            <div class="info-box">
                <div class="text-sm"><span class="label">Waybill No:</span> <span class="value">{{ $waybill->waybill_number }}</span></div>
                <div class="text-sm"><span class="label">Date:</span> <span class="value">{{ $waybill->created_at->format('M d, Y') }}</span></div>
            </div>
        </div>
        <div class="col">
            <div class="info-box">
                <div class="text-sm"><span class="label">Reference No:</span> <span class="value">{{ $waybill->deliveryRequest->reference_number ?? 'N/A' }}</span></div>
                <div class="text-sm"><span class="label">Delivery Type:</span> <span class="value">Branch to Branch</span></div>
            </div>
        </div>
    </div>

    <!-- Shipper & Consignee -->
    <div class="row section">
        <div class="col">
            <div class="info-box">
                <div class="section-title">Shipper Information</div>
                <div class="text-sm"><span class="label">Name:</span> <span class="value">{{ $waybill->deliveryRequest->sender->name ?? $waybill->deliveryRequest->sender->company_name ?? 'N/A' }}</span></div>
                <div class="text-sm"><span class="label">Address:</span> <span class="value">{{ $waybill->deliveryRequest->sender->address ?? 'N/A' }}</span></div>
                <div class="text-sm"><span class="label">Mobile:</span> <span class="value">{{ $waybill->deliveryRequest->sender->mobile ?? 'N/A' }}</span></div>
            </div>
        </div>
        <div class="col">
            <div class="info-box">
                <div class="section-title">Consignee Information</div>
                <div class="text-sm"><span class="label">Name:</span> <span class="value">{{ $waybill->deliveryRequest->receiver->name ?? $waybill->deliveryRequest->receiver->company_name ?? 'N/A' }}</span></div>
                <div class="text-sm"><span class="label">Address:</span> <span class="value">{{ $waybill->deliveryRequest->receiver->address ?? 'N/A' }}</span></div>
                <div class="text-sm"><span class="label">Mobile:</span> <span class="value">{{ $waybill->deliveryRequest->receiver->mobile ?? 'N/A' }}</span></div>
            </div>
        </div>
    </div>

    <!-- Route Information (split into Pickup and Drop-off) -->
    <div class="row section">
        <div class="col">
            <div class="info-box">
                <div class="section-title">Pickup Branch/Region</div>
                <div class="text-sm"><span class="label">Name:</span> <span class="value">{{ $waybill->deliveryRequest->pickUpRegion->name ?? $waybill->deliveryRequest->pick_up_region->name ?? 'N/A' }}</span></div>
                <div class="text-sm"><span class="label">Address:</span> <span class="value">{{ $waybill->deliveryRequest->pickUpRegion->address ?? $waybill->deliveryRequest->pick_up_region->address ?? $waybill->deliveryRequest->pickUpRegion->warehouse_address ?? $waybill->deliveryRequest->pick_up_region->warehouse_address ?? 'N/A' }}</span></div>
            </div>
        </div>
        <div class="col">
            <div class="info-box">
                <div class="section-title">Drop-off Branch/Region</div>
                <div class="text-sm"><span class="label">Name:</span> <span class="value">{{ $waybill->deliveryRequest->dropOffRegion->name ?? $waybill->deliveryRequest->drop_off_region->name ?? 'N/A' }}</span></div>
                <div class="text-sm"><span class="label">Address:</span> <span class="value">{{ $waybill->deliveryRequest->dropOffRegion->warehouse_address ?? $waybill->deliveryRequest->drop_off_region->warehouse_address ?? $waybill->deliveryRequest->dropOffRegion->address ?? $waybill->deliveryRequest->drop_off_region->address ?? 'N/A' }}</span></div>
            </div>
        </div>
    </div>

    <!-- Package Info -->
    <div class="section">
        <div class="section-title">Package Information</div>
        <table>
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th>Weight (kg)</th>
                    <th>Dimensions (cm)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($waybill->deliveryRequest->packages ?? [] as $package)
                <tr>
                    <td>{{ $package->item_code ?? 'N/A' }}</td>
                    <td>{{ $package->item_name ?? 'Unspecified Item' }}</td>
                    <td>{{ $package->weight ?? 'N/A' }}</td>
                    <td>{{ $package->length }}x{{ $package->width }}x{{ $package->height }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4">No packages found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Payment Information -->
    <div class="section">
        <div class="section-title">Payment Information</div>
        <div class="row">
            <div class="col">
                <div class="text-sm"><span class="label">Payment Type:</span> <span class="value">{{ strtoupper($waybill->deliveryRequest->payment_type ?? 'N/A') }}</span></div>
                <div class="text-sm"><span class="label">Method:</span> <span class="value">{{ strtoupper($waybill->deliveryRequest->payment_method ?? 'N/A') }}</span></div>
                <div class="text-sm">
                    <span class="label">Payment Terms:</span>
                    <span class="value">
                        @php
                            $terms = $waybill->deliveryRequest->payment_terms ?? null;
                            echo $terms === 'net_7' ? 'Net 7' :
                                 ($terms === 'net_15' ? 'Net 15' :
                                 ($terms === 'net_30' ? 'Net 30' :
                                 ($terms === 'cnd' ? 'CND' : ($terms ?? 'N/A'))));
                        @endphp
                    </span>
                </div>
                <div class="text-sm">
                    <span class="label">Due Date:</span>
                    <span class="value">
                        {{ $waybill->deliveryRequest->payment_due_date ? \Carbon\Carbon::parse($waybill->deliveryRequest->payment_due_date)->format('M d, Y') : 'N/A' }}
                    </span>
                </div>
            </div>
            <div class="col">
                <div class="text-sm"><span class="label">Total Amount:</span> <span class="value">{{ number_format($waybill->deliveryRequest->total_price ?? 0, 2) }}</span></div>
                <div class="text-sm">
                    <span class="label">Status:</span>
                    <span class="value">
                        @if($waybill->deliveryRequest->payment_type === 'prepaid')
                            {{ $waybill->deliveryRequest->payment_status === 'paid' && $waybill->deliveryRequest->payment_verified ? 'PAID' : 'PENDING' }}
                        @else
                            {{ $order && $order->status === 'completed' ? 'COLLECTED' : 'TO BE COLLECTED' }}
                        @endif
                    </span>
                </div>
                @if($waybill->deliveryRequest->payment_type === 'prepaid' && $waybill->deliveryRequest->payment_status === 'paid' && $waybill->deliveryRequest->payment && $waybill->deliveryRequest->payment_verified)
                    <div class="text-sm"><span class="label">Paid At:</span> <span class="value">{{ $waybill->deliveryRequest->payment->created_at->format('M d, Y H:i') ?? 'N/A' }}</span></div>
                @endif
            </div>
        </div>
    </div>

    <!-- Delivery Assignment -->
    @if($order && $order->driver)
    <div class="section">
        <div class="section-title">Delivery Assignment</div>
        <div class="text-sm"><span class="label">Driver:</span> <span class="value">{{ $order->driver->name ?? 'N/A' }}</span></div>
        <div class="text-sm"><span class="label">Truck:</span> <span class="value">{{ $order->truck->license_plate ?? 'N/A' }}</span></div>
    </div>
    @endif

    <!-- Footer Message -->
    <div class="footer border-t pt-4">
        <div class="flex">
            <div>
                <span class="label">Received By:</span>
                <span class="value">
                    {{ $waybill->deliveryRequest->receiver->name ?? $waybill->deliveryRequest->receiver->company_name ?? 'N/A' }}
                </span>
            </div>
            <div>
                <span class="label">Truck Plate:</span>
                <span class="value">
                    {{ $order->truck->license_plate ?? 'N/A' }}
                </span>
            </div>
        </div>
        <p class="mt-2">Thank you for choosing Infinitrix Express!</p>
        <div class="notice">
            This is the final/complete waybill. All delivery and payment details are finalized.
        </div>
    </div>
</body>
</html>
