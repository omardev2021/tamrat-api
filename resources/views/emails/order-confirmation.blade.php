@php
    $green = '#7C9D64';
    $fmt = function ($v) { return number_format((float) ($v ?? 0), 2) . ' ر.س'; };
    $firstName = trim(strtok((string) ($order->name ?? ''), ' '));
    $logo = 'https://tamratdates.com/logo.webp';
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>تأكيد الطلب</title>
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
                                <td width="64" height="64" align="center" valign="middle" style="background:{{ $green }};border-radius:50%;color:#ffffff;font-size:34px;font-weight:bold;">&#10003;</td>
                            </tr>
                        </table>
                        <div style="font-size:13px;color:{{ $green }};font-weight:bold;letter-spacing:.3px;margin-top:16px;">طلب رقم #{{ $order->id }}</div>
                        <h1 style="font-size:24px;font-weight:bold;margin:6px 0 8px;color:#1a1a1a;">
                            @if($firstName) شكراً لك، {{ $firstName }}! @else شكراً لك! @endif
                        </h1>
                        <p style="font-size:15px;color:#3d3d3d;margin:0;font-weight:bold;">تم تأكيد طلبك وجاري العمل على شحنه.</p>
                    </td>
                </tr>

                <!-- Order summary -->
                <tr>
                    <td style="padding:22px 24px 4px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6e8eb;border-radius:12px;">
                            <tr>
                                <td style="padding:18px 18px 4px;">
                                    <div style="font-size:15px;font-weight:bold;margin-bottom:12px;">ملخص الطلب</div>
                                </td>
                            </tr>

                            @foreach($items as $li)
                                @php
                                    $p = $li->product ?? null;
                                    $name = $p ? ($p->name_ar ?: $p->name_en) : 'منتج';
                                    $img = $p->image_path ?? null;
                                    $lineTotal = (float) $li->price * (int) $li->qty;
                                @endphp
                                <tr>
                                    <td style="padding:6px 18px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="56" valign="top">
                                                    @if($img)
                                                        <img src="{{ $img }}" alt="" width="52" height="52" style="width:52px;height:52px;border-radius:8px;object-fit:cover;border:1px solid #eceef0;display:block;">
                                                    @endif
                                                </td>
                                                <td valign="middle" style="padding:0 10px;font-size:14px;font-weight:bold;color:#2b2b2b;">
                                                    {{ $name }}
                                                    <span style="display:block;font-size:12px;color:#8a8f98;font-weight:normal;margin-top:2px;">الكمية: {{ $li->qty }}</span>
                                                </td>
                                                <td valign="middle" align="left" style="font-size:14px;font-weight:bold;color:#2b2b2b;white-space:nowrap;">
                                                    {{ $fmt($lineTotal) }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            @endforeach

                            <tr><td style="padding:8px 18px;"><div style="border-top:1px solid #eceef0;"></div></td></tr>

                            <tr>
                                <td style="padding:2px 18px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#6b7280;">
                                        <tr>
                                            <td style="padding:4px 0;">المجموع الفرعي</td>
                                            <td align="left" style="padding:4px 0;color:#2b2b2b;">{{ $fmt($order->itemsPrice) }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:4px 0;">الشحن</td>
                                            <td align="left" style="padding:4px 0;">
                                                @if((float) $order->shippingPrice === 0.0)
                                                    <span style="color:{{ $green }};font-weight:bold;">مجاني</span>
                                                @else
                                                    <span style="color:#2b2b2b;">{{ $fmt($order->shippingPrice) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr><td style="padding:6px 18px;"><div style="border-top:1px solid #eceef0;"></div></td></tr>

                            <tr>
                                <td style="padding:2px 18px 16px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="font-size:17px;font-weight:bold;color:#1a1a1a;">الإجمالي</td>
                                            <td align="left" style="font-size:18px;font-weight:bold;color:#1a1a1a;">{{ $fmt($order->totalPrice) }}</td>
                                        </tr>
                                        @if((float) $order->taxPrice > 0)
                                            <tr>
                                                <td colspan="2" align="left" style="font-size:12px;color:#8a8f98;padding-top:4px;">
                                                    شامل ضريبة القيمة المضافة {{ number_format((float) $order->taxPrice, 2) }} ر.س
                                                </td>
                                            </tr>
                                        @endif
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Shipping & contact -->
                <tr>
                    <td style="padding:14px 24px 4px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6e8eb;border-radius:12px;">
                            <tr>
                                <td style="padding:18px;">
                                    <div style="font-size:15px;font-weight:bold;margin-bottom:12px;">تفاصيل الشحن والتواصل</div>
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                        <tr valign="top">
                                            <td width="50%" style="padding:0 0 12px;font-size:13px;color:#2b2b2b;line-height:1.6;">
                                                <div style="font-size:11px;color:#8a8f98;font-weight:bold;text-transform:uppercase;margin-bottom:3px;">معلومات التواصل</div>
                                                @if($order->name)<div>{{ $order->name }}</div>@endif
                                                @if($order->phone)<div dir="ltr" style="text-align:right;">+{{ ltrim($order->phone, '+') }}</div>@endif
                                                @if($order->email)<div dir="ltr" style="text-align:right;">{{ $order->email }}</div>@endif
                                            </td>
                                            <td width="50%" style="padding:0 0 12px;font-size:13px;color:#2b2b2b;line-height:1.6;">
                                                <div style="font-size:11px;color:#8a8f98;font-weight:bold;text-transform:uppercase;margin-bottom:3px;">عنوان الشحن</div>
                                                @if($order->address)<div>{{ $order->address }}</div>@endif
                                                <div>{{ trim(($order->city ? $order->city : '') . ($order->city && $order->country ? '، ' : '') . ($order->country ? $order->country : '')) }}</div>
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <td style="font-size:13px;color:#2b2b2b;">
                                                <div style="font-size:11px;color:#8a8f98;font-weight:bold;text-transform:uppercase;margin-bottom:3px;">طريقة الدفع</div>
                                                <span style="color:{{ $green }};font-weight:bold;">&#10003;</span> تم الدفع إلكترونياً
                                            </td>
                                            <td style="font-size:13px;color:#2b2b2b;">
                                                <div style="font-size:11px;color:#8a8f98;font-weight:bold;text-transform:uppercase;margin-bottom:3px;">طريقة الشحن</div>
                                                توصيل خلال 2-5 أيام عمل
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- CTA -->
                <tr>
                    <td align="center" style="padding:20px 24px 6px;">
                        <a href="https://tamratdates.com" style="display:inline-block;background:{{ $green }};color:#ffffff;text-decoration:none;font-size:15px;font-weight:bold;padding:13px 34px;border-radius:10px;">متابعة التسوق</a>
                    </td>
                </tr>

                <!-- Help -->
                <tr>
                    <td align="center" style="padding:8px 24px 4px;font-size:13px;color:#8a8f98;">
                        تحتاج مساعدة؟
                        <a href="https://wa.me/966548036906" style="color:{{ $green }};font-weight:bold;text-decoration:none;">تواصل معنا عبر واتساب</a>
                    </td>
                </tr>

                <!-- Brand -->
                <tr>
                    <td align="center" style="padding:18px 24px 30px;">
                        <img src="{{ $logo }}" alt="Tamrat Dates" height="32" style="height:32px;opacity:.7;">
                    </td>
                </tr>

            </table>
            <div style="font-size:11px;color:#b0b4ba;margin-top:14px;">تمرات · tamratdates.com</div>
        </td>
    </tr>
</table>
</body>
</html>
