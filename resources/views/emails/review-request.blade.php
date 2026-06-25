@php
    $green = '#7C9D64';
    $logo = 'https://tamratdates.com/logo.webp';
    $name = trim($firstName) ?: 'صديقنا';
    $url = $reviewUrl ?: 'https://wa.me/966548036906';
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>مذاقٌ يستحقّ العودة إليه</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,'Segoe UI',Tahoma,sans-serif;color:#1a1a1a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #e6e8eb;border-radius:14px;">

                <tr>
                    <td align="center" style="padding:34px 28px 6px;">
                        <div style="font-size:34px;line-height:1;letter-spacing:3px;">⭐️⭐️⭐️⭐️⭐️</div>
                        <h1 style="font-size:23px;font-weight:bold;margin:16px 0 8px;color:#1a1a1a;">
                            مذاقٌ يستحقّ العودة إليه
                        </h1>
                        <p style="font-size:15px;line-height:1.8;color:#3d3d3d;margin:0;">
                            نتمنى وصلتك طازجة بحلاوتها الطبيعية وعجبتك يا {{ $name }} 🌴 رأيك يهمّنا فعلًا،
                            ويساعد غيرك يلقى صنفه المفضّل — وما يأخذ منك أكثر من دقيقة.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:18px 28px 6px;">
                        <a href="{{ $url }}"
                           style="display:inline-block;background:{{ $green }};color:#ffffff;text-decoration:none;font-size:16px;font-weight:bold;padding:14px 40px;border-radius:12px;">
                            شاركنا تجربتك
                        </a>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:12px 28px 4px;font-size:13px;color:#8a8f98;line-height:1.7;">
                        ما كانت التجربة بمستوى توقعاتك؟ ردّ على هذا البريد أو
                        <a href="https://wa.me/966548036906" style="color:{{ $green }};font-weight:bold;text-decoration:none;">راسلنا على واتساب</a>
                        ونصلحها لك فورًا — رضاك يهمّنا أولًا.
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
