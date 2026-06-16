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
    <title>وقت التجديد</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,'Segoe UI',Tahoma,sans-serif;color:#1a1a1a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #e6e8eb;border-radius:14px;">

                <tr>
                    <td align="center" style="padding:34px 28px 6px;">
                        <div style="font-size:42px;line-height:1;">🌿</div>
                        <h1 style="font-size:23px;font-weight:bold;margin:14px 0 8px;color:#1a1a1a;">
                            وقت تجديد المخزون يا {{ $name }}؟
                        </h1>
                        <p style="font-size:15px;line-height:1.8;color:#3d3d3d;margin:0;">
                            مرّ نحو شهر على آخر طلب لك من تمرات.
                            @if($lastProduct)
                                إذا قارب <strong>{{ $lastProduct }}</strong> على الانتهاء، جدّد مخزونك قبل أن ينفد 🌴
                            @else
                                إذا قاربت تمورك على الانتهاء، جدّد مخزونك قبل أن ينفد 🌴
                            @endif
                        </p>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:18px 28px 6px;">
                        <a href="https://tamratdates.com/shopping"
                           style="display:inline-block;background:{{ $green }};color:#ffffff;text-decoration:none;font-size:16px;font-weight:bold;padding:14px 38px;border-radius:12px;">
                            اطلب مرة أخرى
                        </a>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:10px 28px 4px;">
                        <p style="font-size:13.5px;color:{{ $green }};font-weight:bold;margin:0;">
                            🚚 شحن مجاني للطلبات فوق 250 ر.س · توصيل خلال 2-5 أيام
                        </p>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:14px 28px 4px;font-size:13px;color:#8a8f98;">
                        تحتاج مساعدة في اختيار الصنف؟
                        <a href="https://wa.me/966548036906" style="color:{{ $green }};font-weight:bold;text-decoration:none;">تواصل معنا عبر واتساب</a>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:18px 28px 14px;">
                        <a href="https://tamratdates.com/best-saudi-dates" style="font-size:12.5px;color:#8a8f98;text-decoration:underline;">
                            دليل أنواع التمور — أيها يناسبك؟
                        </a>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:6px 28px 30px;">
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
