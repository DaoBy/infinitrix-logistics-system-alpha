<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Waybill - {{ $waybill->waybill_number }} (Complete)</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            color: #000; 
            background: #fff; 
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .header h1 { 
            margin: 0; 
            font-size: 24px; 
            font-weight: bold; 
        }
        .header p { 
            margin: 5px 0 0; 
            font-size: 16px; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px; 
        }
        th, td { 
            border: 1px solid #000; 
            padding: 8px; 
            text-align: left; 
            font-size: 12px; 
            vertical-align: top;
        }
        th { 
            background: #f0f0f0; 
            font-weight: bold; 
        }
        .section-title { 
            font-weight: bold; 
            font-size: 14px; 
            margin-bottom: 10px; 
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        .info-table { margin-bottom: 25px; }
        .footer { 
            margin-top: 30px; 
            text-align: center; 
            font-size: 12px; 
            border-top: 1px solid #000;
            padding-top: 15px;
        }
        .label { font-weight: bold; }
        .text-center { text-align: center; }
        .mb-3 { margin-bottom: 15px; }
        .mt-3 { margin-top: 15px; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>INFINITRIX EXPRESS CARGO</h1>
        <p>DELIVERY RECEIPT / WAYBILL (COMPLETED)</p>
    </div>

    <!-- Waybill Information -->
    <table class="info-table">
        <tr>
            <th colspan="2" class="section-title">Waybill Information</th>
        </tr>
        <tr>
            <td width="50%">
                <span class="label">Waybill No:</span> {{ $waybill->waybill_number }}<br>
                <span class="label">Date:</span> {{ $waybill->created_at->format('M d, Y') }}
            </td>
            <td width="50%">
                <span class="label">Reference No:</span> {{ $waybill->deliveryRequest->reference_number ?? 'N/A' }}<br>
                <span class="label">Delivery Type:</span> Branch to Branch
            </td>
        </tr>
    </table>

    <!-- Shipper & Consignee -->
    <table class="info-table">
        <tr>
            <th colspan="2" class="section-title">Customer Information</th>
        </tr>
        <tr>
            <td width="50%">
                <span class="label">SHIPPER</span><br>
                <span class="label">Name:</span> {{ $waybill->deliveryRequest->sender->name ?? $waybill->deliveryRequest->sender->company_name ?? 'N/A' }}<br>
                <span class="label">Address:</span> {{ $waybill->deliveryRequest->sender->address ?? 'N/A' }}<br>
                <span class="label">Mobile:</span> {{ $waybill->deliveryRequest->sender->mobile ?? 'N/A' }}
            </td>
            <td width="50%">
                <span class="label">CONSIGNEE</span><br>
                <span class="label">Name:</span> {{ $waybill->deliveryRequest->receiver->name ?? $waybill->deliveryRequest->receiver->company_name ?? 'N/A' }}<br>
                <span class="label">Address:</span> {{ $waybill->deliveryRequest->receiver->address ?? 'N/A' }}<br>
                <span class="label">Mobile:</span> {{ $waybill->deliveryRequest->receiver->mobile ?? 'N/A' }}
            </td>
        </tr>
    </table>

    <!-- Route Information -->
    <table class="info-table">
        <tr>
            <th colspan="2" class="section-title">Route Information</th>
        </tr>
        <tr>
            <td width="50%">
                <span class="label">PICKUP BRANCH/REGION</span><br>
                <span class="label">Name:</span> {{ $waybill->deliveryRequest->pickUpRegion->name ?? $waybill->deliveryRequest->pick_up_region->name ?? 'N/A' }}<br>
                <span class="label">Address:</span> {{ $waybill->deliveryRequest->pickUpRegion->address ?? $waybill->deliveryRequest->pick_up_region->address ?? $waybill->deliveryRequest->pickUpRegion->warehouse_address ?? $waybill->deliveryRequest->pick_up_region->warehouse_address ?? 'N/A' }}
            </td>
            <td width="50%">
                <span class="label">DROP-OFF BRANCH/REGION</span><br>
                <span class="label">Name:</span> {{ $waybill->deliveryRequest->dropOffRegion->name ?? $waybill->deliveryRequest->drop_off_region->name ?? 'N/A' }}<br>
                <span class="label">Address:</span> {{ $waybill->deliveryRequest->dropOffRegion->warehouse_address ?? $waybill->deliveryRequest->drop_off_region->warehouse_address ?? $waybill->deliveryRequest->dropOffRegion->address ?? $waybill->deliveryRequest->drop_off_region->address ?? 'N/A' }}
            </td>
        </tr>
    </table>

    <!-- Package Information -->
    <table class="info-table">
        <tr>
            <th colspan="4" class="section-title">Package Information</th>
        </tr>
        <tr>
            <th width="20%">Item Code</th>
            <th width="40%">Description</th>
            <th width="20%">Weight (kg)</th>
            <th width="20%">Dimensions (cm)</th>
        </tr>
        @forelse($waybill->deliveryRequest->packages ?? [] as $package)
        <tr>
            <td>{{ $package->item_code ?? 'N/A' }}</td>
            <td>{{ $package->item_name ?? 'Unspecified Item' }}</td>
            <td>{{ $package->weight ?? 'N/A' }}</td>
            <td>{{ $package->length }}x{{ $package->width }}x{{ $package->height }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="text-center">No packages found</td>
        </tr>
        @endforelse
    </table>

    <!-- Payment Information -->
    <table class="info-table">
        <tr>
            <th colspan="3" class="section-title">Payment Information</th>
        </tr>
        <tr>
            <td width="33%">
                <span class="label">Payment Type:</span><br>
                {{ strtoupper($waybill->deliveryRequest->payment_type ?? 'N/A') }}<br><br>
                
                <span class="label">Method:</span><br>
                {{ strtoupper($waybill->deliveryRequest->payment_method ?? 'N/A') }}
            </td>
            <td width="33%">
                <span class="label">Payment Terms:</span><br>
                @if($waybill->deliveryRequest->payment_type === 'postpaid')
                    @php
                        $terms = $waybill->deliveryRequest->payment_terms ?? null;
                        echo $terms === 'net_7' ? 'Net 7' :
                             ($terms === 'net_15' ? 'Net 15' :
                             ($terms === 'net_30' ? 'Net 30' :
                             ($terms === 'cnd' ? 'CND' : 'N/A')));
                    @endphp
                @else
                    For Postpaid Only
                @endif
                <br><br>
                
                <span class="label">Due Date:</span><br>
                @if($waybill->deliveryRequest->payment_type === 'postpaid')
                    {{ $waybill->deliveryRequest->payment_due_date ? \Carbon\Carbon::parse($waybill->deliveryRequest->payment_due_date)->format('M d, Y') : 'N/A' }}
                @else
                    For Postpaid Only
                @endif
            </td>
            <td width="34%">
                <span class="label">Total Amount:</span><br>
                <strong>P{{ number_format($waybill->deliveryRequest->total_price ?? 0, 2) }}</strong><br><br>
                
                <span class="label">Status:</span><br>
                @php
                    // ✅ USE THE SAME LOGIC AS THE CONTROLLER
                    $paymentType = $waybill->deliveryRequest->payment_type ?? null;
                    $paymentMethod = $waybill->deliveryRequest->payment_method ?? null;
                    $paymentStatus = $waybill->deliveryRequest->payment_status ?? null;
                    $paymentVerified = $waybill->deliveryRequest->payment_verified ?? false;
                    
                    $isPaid = false;
                    if ($paymentType === 'prepaid' && $paymentMethod === 'cash') {
                        // Cash prepaid is always considered paid
                        $isPaid = true;
                        echo '<span style="color: #059669; font-weight: bold;">PAID</span>';
                    } elseif ($paymentType === 'prepaid') {
                        // Other prepaid methods need verification
                        $isPaid = $paymentStatus === 'paid' && $paymentVerified;
                        echo $isPaid ? 
                             '<span style="color: #059669; font-weight: bold;">PAID</span>' : 
                             '<span style="color: #d97706; font-weight: bold;">PENDING PAYMENT</span>';
                    } else {
                        // Postpaid
                        $orderStatus = $order->status ?? null;
                        echo $orderStatus === 'completed' ? 
                             '<span style="color: #059669; font-weight: bold;">COLLECTED</span>' : 
                             '<span style="color: #2563eb; font-weight: bold;">TO BE COLLECTED</span>';
                    }
                @endphp
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        <div class="mt-3">
            <p>Thank you for choosing Infinitrix Express!</p>
            <p><em>This is the final/complete waybill. All delivery and payment details are finalized.</em></p>
        </div>
    </div>
</body>
</html>