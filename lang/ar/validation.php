<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'required_with' => 'حقل :attribute مطلوب.',
    'email' => 'يجب أن يكون :attribute بريدًا إلكترونيًا صحيحًا.',
    'unique' => 'قيمة :attribute مستخدمة مسبقًا.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'min' => ['string' => 'يجب ألا يقل :attribute عن :min أحرف.', 'numeric' => 'يجب ألا يقل :attribute عن :min.'],
    'max' => ['string' => 'يجب ألا يزيد :attribute عن :max حرفًا.', 'numeric' => 'يجب ألا يزيد :attribute عن :max.'],
    'url' => 'رابط :attribute غير صحيح.',
    'numeric' => 'يجب أن تكون قيمة :attribute رقمًا.',
    'integer' => 'يجب أن تكون قيمة :attribute عددًا صحيحًا.',
    'exists' => 'قيمة :attribute المحددة غير موجودة.',
    'in' => 'قيمة :attribute المحددة غير صحيحة.',
    'size' => ['string' => 'يجب أن يتكون :attribute من :size أحرف.'],
    'alpha' => 'يجب أن يحتوي :attribute على حروف فقط.',
    'gt' => ['numeric' => 'يجب أن تكون قيمة :attribute أكبر من :value.'],
    'attributes' => [
        'name' => 'الاسم', 'email' => 'البريد الإلكتروني', 'phone' => 'رقم الهاتف',
        'password' => 'كلمة المرور', 'source_url' => 'رابط السلة', 'source_currency' => 'العملة',
        'currency' => 'العملة', 'rate_to_lyd' => 'سعر الصرف', 'status' => 'الحالة',
    ],
];
