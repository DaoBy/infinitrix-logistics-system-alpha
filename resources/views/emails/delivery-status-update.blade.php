<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Infinitrix Cargo Express - Delivery Status Update</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f9f9f9; color: #222; margin: 0; padding: 20px;">
    <div style="max-width: 620px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 40px;">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid #e5e7eb;">
            <h1 style="color: #22c55e; margin: 0 0 8px 0; font-size: 28px; font-weight: bold;">Infinitrix Cargo Express</h1>
            <p style="color: #666; margin: 0; font-size: 16px; text-transform: uppercase; letter-spacing: 1px;">Delivery Status Update</p>
        </div>

        <!-- Greeting -->
        <div style="margin-bottom: 24px;">
            <p style="font-size: 16px; margin: 0; color: #374151;">
                Dear <strong style="color: #22c55e;">{{ $recipientType === 'sender' ? $deliveryOrder->deliveryRequest->sender->name : $deliveryOrder->deliveryRequest->receiver->name }}</strong>,
            </p>
        </div>

        <!-- Status Banner -->
        <div style="background: {{ $status === 'delivered' ? '#f0fdf4' : '#fffbeb' }}; 
                    border: 1px solid {{ $status === 'delivered' ? '#bbf7d0' : '#fed7aa' }}; 
                    border-radius: 6px; padding: 20px; margin-bottom: 32px;">
            <h2 style="color: {{ $status === 'delivered' ? '#166534' : '#92400e' }}; 
                       margin: 0 0 8px 0; font-size: 18px; font-weight: bold;">
                @if($status === 'delivered')
                    Delivery Completed Successfully
                @else
                    Delivery Requires Review
                @endif
            </h2>
            <p style="color: {{ $status === 'delivered' ? '#166534' : '#92400e' }}; margin: 0; font-size: 14px; line-height: 1.5;">
                @if($status === 'delivered')
                    Your delivery has been successfully processed and is ready for pickup at the designated location.
                @else
                    There are issues with your delivery that require attention. Please contact our support team for assistance.
                @endif
            </p>
        </div>

        <!-- Delivery Information - 2x2 Grid -->
        <div style="margin-bottom: 32px;">
            <h3 style="color: #22c55e; margin: 0 0 16px 0; font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">
                Delivery Information
            </h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Column 1 -->
                <div>
                    <div style="margin-bottom: 20px;">
                        <p style="margin: 0 0 6px 0; font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Reference Number</p>
                        <p style="margin: 0; font-size: 15px; color: #111827; font-weight: 600;">
                            {{ $deliveryOrder->deliveryRequest->reference_number }}
                        </p>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <p style="margin: 0 0 6px 0; font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Current Status</p>
                        <p style="margin: 0; font-size: 15px; color: #111827; font-weight: 600;">
                            @if($status === 'delivered')
                                <span style="color: #22c55e;">Delivered</span>
                            @else
                                <span style="color: #f59e0b;">Needs Review</span>
                            @endif
                        </p>
                    </div>
                </div>
                
                <!-- Column 2 -->
                <div>
                    <div style="margin-bottom: 20px;">
                        <p style="margin: 0 0 6px 0; font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Receiver Name</p>
                        <p style="margin: 0; font-size: 15px; color: #111827; font-weight: 600;">
                            {{ $deliveryOrder->deliveryRequest->receiver->name }}
                        </p>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <p style="margin: 0 0 6px 0; font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Destination</p>
                        <p style="margin: 0; font-size: 15px; color: #111827; font-weight: 600;">
                            {{ $deliveryOrder->deliveryRequest->dropOffRegion->name ?? 'Unknown' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

      <!-- Instructions Section -->
<div style="background: #f8fafc; border-radius: 6px; padding: 24px; margin-bottom: 32px;">
    <h3 style="color: #22c55e; margin: 0 0 16px 0; font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">
        @if($status === 'delivered')
            Pickup Instructions
        @else
            Required Actions
        @endif
    </h3>
    
    <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
        @if($status === 'delivered')
        <ul style="margin: 0; padding-left: 20px; color: #374151; font-size: 14px; line-height: 1.6;">
            <li style="margin-bottom: 10px;">Proceed to the designated pickup location during business hours</li>
            <li style="margin-bottom: 10px;">Present valid government-issued identification</li>
            <li style="margin-bottom: 10px;">Provide your reference number for verification</li>
            <li style="margin-bottom: 0;">Inspect all items before leaving the facility</li>
        </ul>
        @else
        <ul style="margin: 0; padding-left: 20px; color: #374151; font-size: 14px; line-height: 1.6;">
            <li style="margin-bottom: 10px;">Contact our support team immediately to discuss delivery issues</li>
            <li style="margin-bottom: 10px;">Have your reference number ready when contacting support</li>
            <li style="margin-bottom: 0;">Check your email for additional instructions from our team</li>
        </ul>
        @endif
    </div>
</div>

        <!-- Contact Information -->
        <div style="text-align: center; padding: 24px; background: #f0fdf4; border-radius: 6px; margin-bottom: 32px;">
            <h3 style="color: #166534; margin: 0 0 12px 0; font-size: 15px; font-weight: bold;">Need Assistance?</h3>
            <p style="margin: 0 0 8px 0; font-size: 14px; color: #374151;">Our support team is available to help you</p>
            <p style="margin: 0; font-size: 15px; color: #22c55e; font-weight: 600;">infinitrixexpress@gmail.com</p>
        </div>

        <!-- Footer -->
        <div style="text-align: center; padding-top: 24px; border-top: 1px solid #e5e7eb;">
            <p style="margin: 0 0 8px 0; font-size: 13px; color: #6b7280;">
                Thank you for choosing Infinitrix Cargo Express
            </p>
            <p style="margin: 0; font-size: 14px; color: #22c55e; font-weight: 600;">
                Infinitrix Cargo Express Team
            </p>
            <p style="margin: 16px 0 0 0; font-size: 12px; color: #9ca3af;">
                &copy; {{ date('Y') }} Infinitrix Cargo Express. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>