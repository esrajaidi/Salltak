<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><title>{{ $subject }}</title></head>
<body style="margin:0;background:#f3f7f9;font-family:Arial,Tahoma,sans-serif;color:#102b3f">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:28px 12px"><tr><td align="center">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#fff;border-radius:18px;overflow:hidden;border:1px solid #dce8ec">
<tr><td style="padding:22px 26px;background:#0b2a3d;color:#fff"><div style="font-size:13px;opacity:.75">إشعار من النظام</div><div style="font-size:22px;font-weight:700;margin-top:4px">{{ $platform }}</div></td></tr>
<tr><td style="padding:28px 26px"><h1 style="font-size:20px;margin:0 0 14px">{{ $subject }}</h1><div style="font-size:15px;line-height:1.9;color:#466273">{!! nl2br(e($body)) !!}</div>@if($url)<p style="margin:24px 0 0"><a href="{{ $url }}" style="display:inline-block;background:#12a6a2;color:#fff;text-decoration:none;padding:11px 18px;border-radius:10px;font-weight:700">فتح التفاصيل</a></p>@endif</td></tr>
<tr><td style="padding:16px 26px;background:#f7fafb;color:#7a909d;font-size:12px">رسالة آلية من {{ $platform }}. يمكنك التحكم في مستلمي هذه الرسائل من إعدادات لوحة الإدارة.</td></tr>
</table>
</td></tr></table>
</body></html>
