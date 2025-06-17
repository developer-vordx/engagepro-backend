<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Dispatch Assigned</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f8f9fa; padding: 20px; color: #333;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <tr>
        <td style="background-color: #007bff; padding: 20px; border-top-left-radius: 8px; border-top-right-radius: 8px; color: #fff; text-align: center;">
            <h1 style="margin: 0; font-size: 24px;">New Dispatch Assigned</h1>
        </td>
    </tr>
    <tr>
        <td style="padding: 30px;">
            <p style="font-size: 16px;">Hello <strong>{{ $load->driver->first_name ?? 'Driver' }}</strong>,</p>

            <p style="font-size: 15px;">You have been assigned a new load. Here are the full details:</p>

            <table style="width: 100%; margin-top: 20px; font-size: 14px; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px; font-weight: bold;">Load Number:</td>
                    <td style="padding: 8px;">{{ $load->number }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; font-weight: bold;">Pickup Location:</td>
                    <td style="padding: 8px;">{{ $load->pickup_address }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; font-weight: bold;">Pickup Date/Time:</td>
                    <td style="padding: 8px;">{{ $load->pickup_date }} / {{ $load->pickup_time }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; font-weight: bold;">Delivery Location:</td>
                    <td style="padding: 8px;">{{ $load->delivery_address }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; font-weight: bold;">Delivery Date/Time:</td>
                    <td style="padding: 8px;">{{ $load->delivery_date }} / {{ $load->delivery_time }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; font-weight: bold;">Equipment Type:</td>
                    <td style="padding: 8px;">{{ $load->equipment_type }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; font-weight: bold;">Commodity:</td>
                    <td style="padding: 8px;">{{ $load->commodity }}</td>
                </tr>
                @if($load->driver_instructions)
                    <tr>
                        <td style="padding: 8px; font-weight: bold;">Instructions:</td>
                        <td style="padding: 8px;">{{ $load->driver_instructions }}</td>
                    </tr>
                @endif
            </table>

            <p style="margin-top: 30px;">Please confirm receipt and reach out if you have any questions.</p>

            <p style="margin-top: 20px;">Thank you,<br><strong>Dispatch Team</strong></p>
        </td>
    </tr>
    <tr>
        <td style="background-color: #f1f1f1; text-align: center; padding: 15px; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; font-size: 12px; color: #777;">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </td>
    </tr>
</table>
</body>
</html>
