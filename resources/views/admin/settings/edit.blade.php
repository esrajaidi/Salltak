@extends('layouts.admin')
@section('title','إعدادات النظام')
@section('admin-content')
<div class="admin-page-header mb-4 reveal is-visible">
    <div class="small text-primary fw-bold mb-1">تخصيص المنصة والتنبيهات</div>
    <h1 class="page-heading">إعدادات النظام</h1>
    <p class="page-subtitle">عدّل بيانات المنصة وحدد عناوين البريد التي تستقبل تنبيهات الطلبات والرسائل والدفعات.</p>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="surface-card admin-panel reveal p-3 p-md-4 p-xl-5 h-100">
            <form method="POST" action="{{ route('admin.settings.update') }}" id="systemSettingsForm">
                @csrf @method('PUT')
                <div class="settings-section-title mb-3"><x-icon name="settings" class="ui-icon"/><div><strong>بيانات المنصة</strong><small>الاسم ووسائل التواصل والمقدمة العامة.</small></div></div>
                <div class="row g-3">
                    <div class="col-12"><label class="form-label">اسم المنصة</label><input class="form-control" name="platform_name" value="{{ old('platform_name',$settings['platform_name']??'سلات ليبيا') }}"></div>
                    <div class="col-md-6"><label class="form-label">الهاتف</label><input class="form-control ltr" name="contact_phone" value="{{ old('contact_phone',$settings['contact_phone']??'') }}"></div>
                    <div class="col-md-6"><label class="form-label">البريد الرئيسي</label><input class="form-control ltr" type="email" name="contact_email" value="{{ old('contact_email',$settings['contact_email']??'') }}"></div>
                    <div class="col-12"><label class="form-label">وصف الصفحة الرئيسية</label><textarea class="form-control" name="home_intro" rows="4">{{ old('home_intro',$settings['home_intro']??'') }}</textarea></div>
                </div>

                <hr class="my-4">
                <div class="settings-section-title mb-3"><x-icon name="mail" class="ui-icon"/><div><strong>إشعارات البريد للإدارة</strong><small>يمكن إضافة أكثر من بريد، وكل بريد يستقبل نفس التنبيه عند تفعيل نوع الحدث.</small></div></div>
                <div class="mb-3">
                    <label class="form-label">عناوين البريد المستلمة للتنبيهات</label>
                    <textarea class="form-control ltr text-start" name="notification_emails" rows="4" placeholder="orders@example.com&#10;admin@example.com">{{ old('notification_emails', str_replace(',', "\n", $settings['notification_emails']??'')) }}</textarea>
                    <div class="form-text">افصل بين العناوين بسطر جديد أو فاصلة. لن يظهر أي عنوان للعميل.</div>
                </div>
                <div class="notification-switch-grid">
                    <label class="notification-setting"><input class="form-check-input" type="checkbox" name="notify_email_new_order" value="1" @checked(old('notify_email_new_order',($settings['notify_email_new_order']??'1')==='1'))><span><strong>طلب جديد</strong><small>إرسال بريد عند تحويل سلة إلى طلب.</small></span></label>
                    <label class="notification-setting"><input class="form-check-input" type="checkbox" name="notify_email_new_message" value="1" @checked(old('notify_email_new_message',($settings['notify_email_new_message']??'1')==='1'))><span><strong>رسالة من العميل</strong><small>إرسال بريد عند وصول رسالة جديدة على الطلب.</small></span></label>
                    <label class="notification-setting"><input class="form-check-input" type="checkbox" name="notify_email_payment" value="1" @checked(old('notify_email_payment',($settings['notify_email_payment']??'1')==='1'))><span><strong>دفعة جديدة</strong><small>إرسال بريد عند تسجيل عربون أو دفعة للتحقق.</small></span></label>
                    <label class="notification-setting"><input class="form-check-input" type="checkbox" name="notify_email_customer_updates" value="1" @checked(old('notify_email_customer_updates',($settings['notify_email_customer_updates']??'1')==='1'))><span><strong>إشعارات العميل بحالة الطلب</strong><small>إرسال بريد للعميل عند قرار أو حالة أو ملاحظة ظاهرة له.</small></span></label>
                </div>

                <div class="alert alert-info border-0 mt-4 mb-0 small">
                    في بيئة التطوير المحلية تُحفظ رسائل البريد في سجل التطبيق. وعند النشر اربط النظام بخادم البريد الفعلي من ملف البيئة حتى تصل التنبيهات إلى العناوين المحددة.
                </div>
                <div class="d-flex justify-content-end mt-4"><button class="btn btn-primary px-4" type="submit"><x-icon name="check" class="ui-icon me-1"/>حفظ التغييرات</button></div>
            </form>
        </div>
    </div>
    <div class="col-xl-5">
        <aside class="surface-card admin-panel p-4 h-100 settings-preview-card">
            <div class="settings-section-title mb-3"><x-icon name="bell" class="ui-icon"/><div><strong>كيف تعمل التنبيهات؟</strong><small>تنبيه داخل النظام + بريد اختياري.</small></div></div>
            <div class="d-grid gap-3">
                <div class="settings-flow-step"><span>1</span><div><strong>العميل يرسل طلبًا أو رسالة</strong><small>تُحفظ العملية أولًا داخل النظام.</small></div></div>
                <div class="settings-flow-step"><span>2</span><div><strong>يصل جرس الإشعارات</strong><small>الإدارة ترى الحدث داخل لوحة التحكم فورًا.</small></div></div>
                <div class="settings-flow-step"><span>3</span><div><strong>يرسل البريد للمستلمين</strong><small>الإرسال لا يوقف طلب العميل حتى لو تعطلت خدمة البريد.</small></div></div>
            </div>
        </aside>
    </div>
</div>
@endsection
