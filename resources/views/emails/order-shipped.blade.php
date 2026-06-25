@php
    $green = '#7C9D64';
    $firstName = trim(strtok((string) ($order->name ?? ''), ' '));
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>طلبك في الطريق</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,'Segoe UI',Tahoma,sans-serif;color:#1a1a1a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #e6e8eb;border-radius:14px;">

                <!-- Header -->
                <tr>
                    <td align="center" style="padding:34px 24px 6px;">
                        <table role="presentation" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="64" height="64" align="center" valign="middle" style="background:{{ $green }};border-radius:50%;color:#ffffff;font-size:32px;">&#128666;</td>
                            </tr>
                        </table>
                        <div style="font-size:13px;color:{{ $green }};font-weight:bold;letter-spacing:.3px;margin-top:16px;">طلب رقم #{{ $order->id }}</div>
                        <h1 style="font-size:24px;font-weight:bold;margin:6px 0 8px;color:#1a1a1a;">
                            @if($firstName) طلبك في الطريق إليك يا {{ $firstName }}! @else طلبك في الطريق إليك! @endif
                        </h1>
                        <p style="font-size:15px;color:#3d3d3d;margin:0;">جهّزناه بعناية، وهو الآن مع شركة الشحن — يصلك خلال ٢–٥ أيام بإذن الله. 🌴</p>
                    </td>
                </tr>

                <!-- Tracking card -->
                <tr>
                    <td style="padding:22px 24px 4px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6e8eb;border-radius:12px;background:#fafbf9;">
                            <tr>
                                <td style="padding:18px 18px 6px;">
                                    <div style="font-size:15px;font-weight:bold;margin-bottom:12px;">تفاصيل الشحن</div>
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#3d3d3d;">
                                        <tr>
                                            <td style="padding:4px 0;color:#6b7280;">شركة الشحن</td>
                                            <td style="padding:4px 0;text-align:left;font-weight:bold;">{{ $order->carrier }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:4px 0;color:#6b7280;">رقم التتبّع</td>
                                            <td style="padding:4px 0;text-align:left;font-weight:bold;direction:ltr;">{{ $order->awb }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            @if($trackingUrl)
                            <tr>
                                <td align="center" style="padding:6px 18px 20px;">
                                    <a href="{{ $trackingUrl }}" style="display:inline-block;background:{{ $green }};color:#ffffff;text-decoration:none;font-size:15px;font-weight:bold;padding:13px 28px;border-radius:9px;">تتبّع شحنتك ←</a>
                                </td>
                            </tr>
                            @else
                            <tr><td style="padding:0 18px 18px;"></td></tr>
                            @endif
                        </table>
                    </td>
                </tr>

                <!-- Items -->
                <tr>
                    <td style="padding:14px 24px 4px;">
                        <div style="font-size:15px;font-weight:bold;margin-bottom:10px;">ما الذي يصلك</div>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6e8eb;border-radius:12px;">
                            @foreach($items as $li)
                                @php
                                    $p = $li->product ?? null;
                                    $name = $p ? ($p->name_ar ?: $p->name_en) : 'منتج';
                                    $img = $p->image_path ?? null;
                                @endphp
                                <tr>
                                    <td style="padding:10px 18px;border-bottom:{{ $loop->last ? '0' : '1px solid #f0f1f2' }};">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="52" valign="middle">
                                                    @if($img)
                                                        <img src="{{ $img }}" alt="" width="48" height="48" style="width:48px;height:48px;border-radius:8px;object-fit:cover;border:1px solid #eceef0;display:block;">
                                                    @endif
                                                </td>
                                                <td valign="middle" style="padding-right:12px;font-size:14px;font-weight:bold;color:#1a1a1a;">{{ $name }}</td>
                                                <td valign="middle" align="left" style="font-size:13px;color:#6b7280;">×{{ $li->qty }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td align="center" style="padding:22px 24px 30px;">
                        <p style="font-size:13px;color:#6b7280;margin:0 0 6px;">عندك أي سؤال؟ راسلنا على واتساب وبنكون سعداء بمساعدتك.</p>
                        <a href="https://wa.me/966548036906" style="font-size:13px;color:{{ $green }};font-weight:bold;text-decoration:none;">واتساب: 966548036906+</a>
                        <p style="font-size:12px;color:#9ca3af;margin:16px 0 0;">تمرات — Tamrat 🌴</p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
