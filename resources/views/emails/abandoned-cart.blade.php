@php
    $green = '#7C9D64';
    $logo = 'https://tamratdates.com/logo.webp';
    $name = trim($firstName) ?: 'صديقنا';
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>سلتك بانتظارك</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,'Segoe UI',Tahoma,sans-serif;color:#1a1a1a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #e6e8eb;border-radius:14px;">

                <tr>
                    <td align="center" style="padding:34px 28px 4px;">
                        <div style="font-size:40px;line-height:1;">🛒</div>
                        <h1 style="font-size:23px;font-weight:bold;margin:14px 0 8px;color:#1a1a1a;">
                            نسيت شيئًا يا {{ $name }}؟
                        </h1>
                        <p style="font-size:15px;line-height:1.8;color:#3d3d3d;margin:0;">
                            سلّتك ما زالت محفوظة — أكمل طلبك قبل أن تنفد الكمية 🌿
                        </p>
                    </td>
                </tr>

                @if(!empty($items) && count($items))
                <tr>
                    <td style="padding:16px 28px 4px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6e8eb;border-radius:12px;">
                            @foreach($items as $it)
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #f0f0f0;font-size:14.5px;color:#2b2b2b;font-weight:bold;">
                                    {{ $it['name'] }}
                                    <span style="color:#8a8f98;font-weight:normal;"> × {{ $it['qty'] }}</span>
                                </td>
                                <td align="left" style="padding:12px 16px;border-bottom:1px solid #f0f0f0;font-size:14.5px;color:#2b2b2b;white-space:nowrap;">
                                    {{ number_format($it['price'] * $it['qty'], 2) }} ر.س
                                </td>
                            </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
                @endif

                <tr>
                    <td align="center" style="padding:18px 28px 6px;">
                        <a href="{{ $resumeUrl }}"
                           style="display:inline-block;background:{{ $green }};color:#ffffff;text-decoration:none;font-size:16px;font-weight:bold;padding:14px 40px;border-radius:12px;">
                            أكمل طلبك
                        </a>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:10px 28px 4px;">
                        <p style="font-size:13.5px;color:{{ $green }};font-weight:bold;margin:0;">
                            🔒 دفع آمن · 🚚 شحن مجاني فوق 250 ر.س
                        </p>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:12px 28px 4px;font-size:13px;color:#8a8f98;">
                        واجهت مشكلة في الدفع؟
                        <a href="https://wa.me/966548036906" style="color:{{ $green }};font-weight:bold;text-decoration:none;">راسلنا على واتساب</a>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:18px 28px 30px;">
                        <img src="{{ $logo }}" alt="Tamrat Dates" height="30" style="height:30px;opacity:.7;">
                    </td>
                </tr>

            </table>
            <div style="font-size:11px;color:#b0b4ba;margin-top:14px;">تمرات · tamratdates.com</div>
        </td>
    </tr>
</table>
</body>
</html>
