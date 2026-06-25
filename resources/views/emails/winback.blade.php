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
    <title>تمورٌ مختارة بعناية، تليق بمن يميّز التميّز</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,'Segoe UI',Tahoma,sans-serif;color:#1a1a1a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #e6e8eb;border-radius:14px;">

                <tr>
                    <td align="center" style="padding:34px 28px 6px;">
                        <div style="font-size:42px;line-height:1;">🌴</div>
                        <h1 style="font-size:23px;font-weight:bold;margin:14px 0 8px;color:#1a1a1a;">
                            تمورٌ مختارة بعناية، تليق بمن يميّز التميّز
                        </h1>
                        <p style="font-size:15px;line-height:1.8;color:#3d3d3d;margin:0;">
                            مرّ وقت على آخر طلب لك يا {{ $name }}… ومن وقتها وصلتنا أصنافٌ
                            طازجة بحلاوتها الطبيعية 🌴 نحب نشوفك من جديد — ونكهتك المفضّلة تنتظرك.
                        </p>
                    </td>
                </tr>

                @if(trim($code))
                <tr>
                    <td align="center" style="padding:14px 28px 0;">
                        <table role="presentation" cellpadding="0" cellspacing="0" style="background:#f4f7f0;border:1px dashed {{ $green }};border-radius:10px;">
                            <tr><td style="padding:12px 26px;font-size:15px;color:#2b2b2b;">
                                استخدم كود <strong style="color:{{ $green }};letter-spacing:1px;">{{ $code }}</strong> عند الطلب
                            </td></tr>
                        </table>
                    </td>
                </tr>
                @endif

                <tr>
                    <td align="center" style="padding:18px 28px 6px;">
                        <a href="https://tamratdates.com/shopping"
                           style="display:inline-block;background:{{ $green }};color:#ffffff;text-decoration:none;font-size:16px;font-weight:bold;padding:14px 38px;border-radius:12px;">
                            اكتشف أصنافنا من جديد
                        </a>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:10px 28px 4px;">
                        <p style="font-size:13.5px;color:{{ $green }};font-weight:bold;margin:0;">
                            🚚 شحن مجاني للطلبات فوق 250 ر.س
                        </p>
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
