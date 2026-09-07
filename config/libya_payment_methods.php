<?php

/*
 | Libya payment catalog for Salltak.
 |
 | Official activity/company names are based on the Central Bank of Libya
 | electronic-payment directory. LYPay QR/API metadata is based on the CBL
 | DevPortal/LYPay public documentation. Where a public merchant API is not
 | published, the system intentionally requires merchant/acquirer documents
 | instead of inventing endpoints or credentials.
 */

return [
    'directory_source' => 'https://cbl.gov.ly/electronic-payment/',
    'schemas' => [
        'lypay' => [
            'title' => 'إعداد LYPay للتاجر',
            'modes' => ['merchant_qr' => 'رمز دفع للتاجر / تحويل فوري', 'manual_verification' => 'رقم حساب مصرفي دولي / تحقق يدوي', 'partner_api' => 'ربط برمجي عبر مصرف أو جهة مرخصة'],
            'fields' => [
                'bank_name' => ['label' => 'المصرف', 'type' => 'text', 'public' => true],
                'account_name' => ['label' => 'اسم صاحب الحساب', 'type' => 'text', 'public' => true],
                'iban' => ['label' => 'رقم الحساب المصرفي الدولي', 'type' => 'text', 'public' => true, 'ltr' => true],
                'account_number' => ['label' => 'رقم الحساب', 'type' => 'text', 'public' => true, 'ltr' => true],
                'merchant_name' => ['label' => 'اسم التاجر', 'type' => 'text', 'public' => true],
                'merchant_account_number' => ['label' => 'رقم حساب التاجر في رمز الدفع', 'type' => 'text', 'public' => false, 'ltr' => true],
                'mcc' => ['label' => 'رمز فئة التاجر', 'type' => 'text', 'public' => false, 'ltr' => true],
                'city' => ['label' => 'المدينة', 'type' => 'text', 'public' => false],
                'qr_value' => ['label' => 'بيانات رمز الدفع للتاجر', 'type' => 'textarea', 'public' => true, 'ltr' => true],
                'qr_image_url' => ['label' => 'رابط صورة رمز الدفع', 'type' => 'url', 'public' => true, 'ltr' => true],
                'internal_reference_prefix' => ['label' => 'بادئة مرجع الطلب', 'type' => 'text', 'public' => false, 'ltr' => true],
                'api_base_url' => ['label' => 'الرابط الأساسي للربط البرمجي عبر الشريك', 'type' => 'url', 'public' => false, 'ltr' => true],
                'api_key' => ['label' => 'مفتاح المصادقة للربط البرمجي', 'type' => 'password', 'public' => false, 'secret' => true, 'ltr' => true],
                'webhook_secret' => ['label' => 'مفتاح توقيع إشعارات الدفع', 'type' => 'password', 'public' => false, 'secret' => true, 'ltr' => true],
            ],
            'activation' => ['any_of' => [['iban','account_number','qr_value','qr_image_url']]],
        ],
        'onepay' => [
            'title' => 'إعداد OnePay للتاجر',
            'modes' => ['merchant_qr' => 'رمز دفع / بيانات المستفيد', 'manual_verification' => 'تحقق يدوي'],
            'fields' => [
                'bank_name' => ['label' => 'المصرف/الجهة', 'type' => 'text', 'public' => true],
                'account_name' => ['label' => 'اسم المستفيد', 'type' => 'text', 'public' => true],
                'beneficiary_account' => ['label' => 'حساب/معرّف المستفيد', 'type' => 'text', 'public' => true, 'ltr' => true],
                'merchant_name' => ['label' => 'اسم التاجر', 'type' => 'text', 'public' => true],
                'merchant_id' => ['label' => 'معرّف التاجر (إن منحته الجهة)', 'type' => 'text', 'public' => false, 'ltr' => true],
                'terminal_id' => ['label' => 'معرّف نقطة الدفع (إن وجد)', 'type' => 'text', 'public' => false, 'ltr' => true],
                'qr_value' => ['label' => 'بيانات رمز الدفع للتاجر', 'type' => 'textarea', 'public' => true, 'ltr' => true],
                'qr_image_url' => ['label' => 'رابط صورة رمز الدفع', 'type' => 'url', 'public' => true, 'ltr' => true],
            ],
            'activation' => ['any_of' => [['beneficiary_account','merchant_id','qr_value','qr_image_url']]],
        ],
        'bank_transfer' => [
            'title' => 'بيانات التحويل المصرفي',
            'modes' => ['manual_verification' => 'تحويل + تحقق يدوي'],
            'fields' => [
                'bank_name' => ['label' => 'اسم المصرف', 'type' => 'text', 'public' => true],
                'branch_name' => ['label' => 'الفرع', 'type' => 'text', 'public' => true],
                'account_name' => ['label' => 'اسم صاحب الحساب', 'type' => 'text', 'public' => true],
                'iban' => ['label' => 'رقم الحساب المصرفي الدولي', 'type' => 'text', 'public' => true, 'ltr' => true],
                'account_number' => ['label' => 'رقم الحساب', 'type' => 'text', 'public' => true, 'ltr' => true],
            ],
            'activation' => ['all_of' => ['account_name'], 'any_of' => [['iban','account_number']]],
        ],
        'qr' => [
            'title' => 'إعداد الدفع عبر الرمز',
            'modes' => ['merchant_qr' => 'رمز دفع للتاجر', 'manual_verification' => 'رمز دفع + تحقق يدوي'],
            'fields' => [
                'provider_name' => ['label' => 'مزود رمز الدفع', 'type' => 'text', 'public' => true],
                'merchant_name' => ['label' => 'اسم التاجر', 'type' => 'text', 'public' => true],
                'qr_value' => ['label' => 'بيانات رمز الدفع أو المرجع', 'type' => 'textarea', 'public' => true, 'ltr' => true],
                'qr_image_url' => ['label' => 'رابط صورة رمز الدفع', 'type' => 'url', 'public' => true, 'ltr' => true],
                'iban' => ['label' => 'رقم الحساب المصرفي الدولي المرتبط (إن وجد)', 'type' => 'text', 'public' => true, 'ltr' => true],
            ],
            'activation' => ['any_of' => [['qr_value','qr_image_url']]],
        ],
        'wallet' => [
            'title' => 'إعداد المحفظة/التاجر',
            'modes' => ['manual_verification' => 'محفظة + تحقق يدوي', 'merchant_qr' => 'رمز دفع للمحفظة'],
            'fields' => [
                'wallet_number' => ['label' => 'رقم/حساب المحفظة', 'type' => 'text', 'public' => true, 'ltr' => true],
                'merchant_name' => ['label' => 'اسم التاجر', 'type' => 'text', 'public' => true],
                'merchant_id' => ['label' => 'معرّف التاجر (إن منحته الشركة)', 'type' => 'text', 'public' => false, 'ltr' => true],
                'phone' => ['label' => 'رقم الهاتف المرتبط', 'type' => 'text', 'public' => true, 'ltr' => true],
                'qr_value' => ['label' => 'رمز الدفع أو مرجع المحفظة', 'type' => 'textarea', 'public' => true, 'ltr' => true],
                'qr_image_url' => ['label' => 'رابط صورة رمز الدفع', 'type' => 'url', 'public' => true, 'ltr' => true],
            ],
            'activation' => ['any_of' => [['wallet_number','merchant_id','phone','qr_value','qr_image_url']]],
        ],
        'card_external' => [
            'title' => 'إعداد قبول البطاقات عبر الجهة المتعاقدة',
            'modes' => ['external_link' => 'رابط دفع خارجي من المزود', 'partner_api' => 'ربط برمجي تعاقدي (موصل مخصص)'],
            'fields' => [
                'acquirer_name' => ['label' => 'المصرف أو معالج الدفع المتعاقد معه', 'type' => 'text', 'public' => true],
                'merchant_name' => ['label' => 'اسم التاجر', 'type' => 'text', 'public' => true],
                'merchant_id' => ['label' => 'معرّف التاجر', 'type' => 'text', 'public' => false, 'ltr' => true],
                'terminal_id' => ['label' => 'معرّف نقطة الدفع', 'type' => 'text', 'public' => false, 'ltr' => true],
                'merchant_contract_reference' => ['label' => 'مرجع عقد التاجر', 'type' => 'text', 'public' => false, 'ltr' => true],
                'checkout_url' => ['label' => 'رابط صفحة الدفع', 'type' => 'url', 'public' => false, 'ltr' => true],
                'return_url' => ['label' => 'رابط العودة بعد الدفع', 'type' => 'url', 'public' => false, 'ltr' => true],
                'callback_url' => ['label' => 'رابط إشعار نتيجة الدفع', 'type' => 'url', 'public' => false, 'ltr' => true],
                'api_base_url' => ['label' => 'الرابط الأساسي للربط البرمجي من وثائق التاجر', 'type' => 'url', 'public' => false, 'ltr' => true],
                'api_key' => ['label' => 'مفتاح الربط البرمجي', 'type' => 'password', 'public' => false, 'secret' => true, 'ltr' => true],
                'secret_key' => ['label' => 'المفتاح السري', 'type' => 'password', 'public' => false, 'secret' => true, 'ltr' => true],
                'webhook_secret' => ['label' => 'مفتاح توقيع إشعار الدفع', 'type' => 'password', 'public' => false, 'secret' => true, 'ltr' => true],
            ],
            'activation' => ['all_of' => ['acquirer_name','merchant_id','checkout_url']],
        ],
        'pos' => [
            'title' => 'إعداد نقطة بيع / نقطة بيع عبر الهاتف',
            'modes' => ['in_person' => 'دفع حضوري عند التسليم/المكتب'],
            'fields' => [
                'acquirer_name' => ['label' => 'المصرف/المعالج', 'type' => 'text', 'public' => true],
                'merchant_name' => ['label' => 'اسم التاجر', 'type' => 'text', 'public' => true],
                'terminal_id' => ['label' => 'معرّف نقطة الدفع أو نقطة البيع عبر الهاتف', 'type' => 'text', 'public' => false, 'ltr' => true],
                'location' => ['label' => 'مكان توفر الجهاز', 'type' => 'text', 'public' => true],
                'phone' => ['label' => 'هاتف التنسيق', 'type' => 'text', 'public' => true, 'ltr' => true],
            ],
            'activation' => ['all_of' => ['acquirer_name','merchant_name']],
        ],
        'cash' => [
            'title' => 'الدفع النقدي',
            'modes' => ['cash' => 'دفع نقدي مع تحقق المسؤول'],
            'fields' => [
                'collection_location' => ['label' => 'مكان استلام النقد', 'type' => 'text', 'public' => true],
                'collection_phone' => ['label' => 'هاتف التنسيق', 'type' => 'text', 'public' => true, 'ltr' => true],
            ],
            'activation' => [],
        ],
        'custom' => [
            'title' => 'إعداد مخصص',
            'modes' => ['manual_verification' => 'تحقق يدوي', 'external_link' => 'رابط دفع خارجي'],
            'fields' => [
                'provider_name' => ['label' => 'اسم المزود', 'type' => 'text', 'public' => true],
                'account_name' => ['label' => 'اسم الحساب/التاجر', 'type' => 'text', 'public' => true],
                'account_number' => ['label' => 'رقم الحساب/المعرّف', 'type' => 'text', 'public' => true, 'ltr' => true],
                'checkout_url' => ['label' => 'رابط الدفع الخارجي', 'type' => 'url', 'public' => false, 'ltr' => true],
            ],
            'activation' => [],
        ],
    ],

    'methods' => [
        [
            'code' => 'lypay', 'name' => 'LYPay - تحويل مصرفي فوري', 'type' => 'bank', 'schema' => 'lypay', 'sort_order' => 10,
            'instructions' => 'حوّل عبر LYPay إلى رقم الحساب المصرفي الدولي أو حساب سلتك، أو امسح رمز الدفع الخاص بالتاجر من تطبيق مصرفك، ثم أدخل رقم العملية أو أرفق الإثبات.',
            'config' => ['provider_name'=>'LYPay','official_activity'=>'خدمة دفع وتحويل فوري بين المصارف / دفع للتاجر / رمز دفع','integration_mode'=>'merchant_qr','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'public_docs_partner_api_restricted','official_source'=>'https://lypay.gov.ly/about-us/'],
        ],
        [
            'code' => 'onepay', 'name' => 'OnePay', 'type' => 'bank', 'schema' => 'onepay', 'sort_order' => 20,
            'instructions' => 'ادفع عبر OnePay باستخدام بيانات المستفيد/التاجر التي تضبطها الإدارة، ثم أدخل رقم العملية أو أرفق إثبات الدفع.',
            'config' => ['provider_name'=>'OnePay','official_activity'=>'خدمة دفع فوري','integration_mode'=>'merchant_qr','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'merchant_docs_required','official_source'=>'https://cbl.gov.ly/en/instant-payment-services-statistics-2/'],
        ],
        [
            'code'=>'bank_transfer','name'=>'تحويل مصرفي تقليدي','type'=>'bank','schema'=>'bank_transfer','sort_order'=>30,
            'instructions'=>'حوّل إلى الحساب أو رقم الحساب المصرفي الدولي المحدد ثم أدخل رقم العملية أو أرفق إيصال التحويل.',
            'config'=>['provider_name'=>'تحويل مصرفي','official_activity'=>'تحويل مصرفي مباشر إلى حساب التاجر','integration_mode'=>'manual_verification','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'merchant_account_required','official_source'=>'https://lypay.gov.ly/about-us/'],
        ],
        [
            'code'=>'numo_qr','name'=>'الدفع عبر رمز الاستجابة السريعة (NUMO)','type'=>'bank','schema'=>'qr','sort_order'=>40,
            'instructions'=>'امسح رمز الدفع المعروض من تطبيق الدفع/المصرف وأكمل العملية ثم أدخل رقم المرجع.',
            'config'=>['provider_name'=>'NUMO','official_activity'=>'معيار QR وطني للدفع','integration_mode'=>'merchant_qr','proof_mode'=>'reference','currency'=>'LYD','availability'=>'online','documentation_status'=>'public_standard','official_source'=>'https://central-bank-of-libya.gitbook.io/devportal/lypay/numo-qr-code-standards'],
        ],
        [
            'code'=>'local_cards','name'=>'بطاقة مصرفية محلية','type'=>'api','schema'=>'card_external','sort_order'=>50,
            'instructions'=>'الدفع ببطاقة محلية عبر المعالج/المصرف المتعاقد معه سلتك. لا تفعل هذه الطريقة قبل إدخال رابط الدفع وبيانات التاجر الرسمية.',
            'config'=>['provider_name'=>'البطاقات المحلية','official_activity'=>'قبول بطاقات محلية عبر مصرف أو معالج مرخص','integration_mode'=>'external_link','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'acquirer_docs_required','official_source'=>'https://cbl.gov.ly/electronic-payment/'],
        ],
        [
            'code'=>'visa','name'=>'Visa','type'=>'api','schema'=>'card_external','sort_order'=>60,
            'instructions'=>'الدفع ببطاقة Visa عبر مصرف/معالج متعاقد معه. مفاتيح Visa نفسها لا توضع مباشرة في سلتك؛ الإعداد يكون من الـAcquirer.',
            'config'=>['provider_name'=>'Visa','official_activity'=>'شبكة بطاقات عالمية عبر مصرف أو معالج دفع','integration_mode'=>'external_link','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'acquirer_docs_required','official_source'=>'https://cbl.gov.ly/payment-and-settlement/'],
        ],
        [
            'code'=>'mastercard','name'=>'Mastercard','type'=>'api','schema'=>'card_external','sort_order'=>70,
            'instructions'=>'الدفع ببطاقة Mastercard عبر مصرف/معالج متعاقد معه. الإعداد يعتمد على وثائق الـAcquirer وليس على مفاتيح Mastercard مباشرة.',
            'config'=>['provider_name'=>'Mastercard','official_activity'=>'شبكة بطاقات عالمية عبر مصرف أو معالج دفع','integration_mode'=>'external_link','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'acquirer_docs_required','official_source'=>'https://cbl.gov.ly/payment-and-settlement/'],
        ],
        [
            'code'=>'pos_softpos','name'=>'نقطة بيع / نقطة بيع عبر الهاتف عند التسليم','type'=>'manual','schema'=>'pos','sort_order'=>80,
            'instructions'=>'الدفع بالبطاقة على جهاز POS/SoftPOS عند الاستلام أو في نقطة التسليم المحددة.',
            'config'=>['provider_name'=>'نقطة بيع / نقطة بيع عبر الهاتف','official_activity'=>'قبول بطاقات حضوري','integration_mode'=>'in_person','proof_mode'=>'none','currency'=>'LYD','availability'=>'delivery_only','documentation_status'=>'acquirer_docs_required','official_source'=>'https://cbl.gov.ly/payment-and-settlement/'],
        ],
        [
            'code'=>'cash','name'=>'دفع نقدي','type'=>'cash','schema'=>'cash','sort_order'=>90,
            'instructions'=>'يمكن دفع العربون أو دفعة نقدية بعد التنسيق مع الإدارة. تُسجل الدفعة وتبقى بانتظار تحقق المسؤول حتى استلام المبلغ فعليًا.',
            'config'=>['provider_name'=>'دفع نقدي','official_activity'=>'دفع نقدي مباشر مع تحقق المسؤول','integration_mode'=>'cash','proof_mode'=>'none','currency'=>'LYD','availability'=>'all','allow_deposit'=>'1','allow_balance'=>'1','documentation_status'=>'internal','official_source'=>'internal'],
        ],
        [
            'code'=>'cash_on_delivery','name'=>'الدفع عند الاستلام','type'=>'cash','schema'=>'cash','sort_order'=>95,
            'instructions'=>'يُدفع الرصيد المتبقي نقدًا عند الاستلام. إذا كان الطلب يحتاج عربونًا فيجب سداد العربون أولًا بطريقة متاحة قبل بدء الشراء.',
            'config'=>['provider_name'=>'الدفع عند الاستلام','official_activity'=>'تحصيل الرصيد نقدًا عند التسليم','integration_mode'=>'cash','proof_mode'=>'none','currency'=>'LYD','availability'=>'delivery_only','allow_deposit'=>'0','allow_balance'=>'1','documentation_status'=>'internal','official_source'=>'internal'],
        ],

        // CBL-licensed electronic wallet providers.
        [
            'code'=>'almadar_wallet','name'=>'محفظة المدار الجديد','type'=>'wallet','schema'=>'wallet','sort_order'=>100,
            'instructions'=>'ادفع إلى محفظة/حساب التاجر المحدد ثم أرسل رقم العملية أو الإثبات.',
            'config'=>['provider_name'=>'المدار الجديد','official_activity'=>'محفظة رقمية','integration_mode'=>'manual_verification','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'merchant_docs_required','official_source'=>'https://cbl.gov.ly/electronic-payment/'],
        ],
        [
            'code'=>'alittihad_international_wallet','name'=>'محفظة شركة الاتحاد الدولي','type'=>'wallet','schema'=>'wallet','sort_order'=>110,
            'instructions'=>'ادفع إلى بيانات المحفظة/التاجر التي تضبطها الإدارة ثم أرسل رقم العملية أو الإثبات.',
            'config'=>['provider_name'=>'شركة الاتحاد الدولي','official_activity'=>'محفظة رقمية','integration_mode'=>'manual_verification','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'merchant_docs_required','official_source'=>'https://cbl.gov.ly/electronic-payment/'],
        ],
        [
            'code'=>'miza_wallet','name'=>'محفظة ميزا للخدمات المالية','type'=>'wallet','schema'=>'wallet','sort_order'=>120,
            'instructions'=>'ادفع إلى بيانات المحفظة/التاجر التي تضبطها الإدارة ثم أرسل رقم العملية أو الإثبات.',
            'config'=>['provider_name'=>'شركة ميزا للخدمات المالية','official_activity'=>'محفظة رقمية','integration_mode'=>'manual_verification','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'merchant_docs_required','official_source'=>'https://cbl.gov.ly/electronic-payment/'],
        ],
        [
            'code'=>'daleel_libya_wallet','name'=>'محفظة دليل ليبيا','type'=>'wallet','schema'=>'wallet','sort_order'=>130,
            'instructions'=>'ادفع إلى بيانات المحفظة/التاجر التي تضبطها الإدارة ثم أرسل رقم العملية أو الإثبات.',
            'config'=>['provider_name'=>'شركة دليل ليبيا','official_activity'=>'محفظة رقمية','integration_mode'=>'manual_verification','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'merchant_docs_required','official_source'=>'https://cbl.gov.ly/electronic-payment/'],
        ],
        [
            'code'=>'fawry_wallet','name'=>'محفظة فوري للدفع الإلكتروني','type'=>'wallet','schema'=>'wallet','sort_order'=>140,
            'instructions'=>'ادفع إلى بيانات المحفظة/التاجر التي تضبطها الإدارة ثم أرسل رقم العملية أو الإثبات.',
            'config'=>['provider_name'=>'شركة فوري لخدمات الدفع الإلكتروني','official_activity'=>'محفظة رقمية','integration_mode'=>'manual_verification','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'merchant_docs_required','official_source'=>'https://cbl.gov.ly/electronic-payment/'],
        ],
        [
            'code'=>'albidaya_wallet','name'=>'محفظة البداية للتقنية المالية','type'=>'wallet','schema'=>'wallet','sort_order'=>150,
            'instructions'=>'ادفع إلى بيانات المحفظة/التاجر التي تضبطها الإدارة ثم أرسل رقم العملية أو الإثبات.',
            'config'=>['provider_name'=>'شركة البداية للتقنية المالية','official_activity'=>'محفظة إلكترونية','integration_mode'=>'manual_verification','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'merchant_docs_required','official_source'=>'https://cbl.gov.ly/electronic-payment/'],
        ],
        [
            'code'=>'runpay_wallet','name'=>'RUNPAY - مساهمات الائتمانية','type'=>'wallet','schema'=>'wallet','sort_order'=>160,
            'instructions'=>'ادفع عبر RUNPAY إلى بيانات التاجر/المحفظة المحددة ثم أرسل رقم العملية أو الإثبات.',
            'config'=>['provider_name'=>'شركة مساهمات الائتمانية','official_activity'=>'محفظة إلكترونية RUNPAY','integration_mode'=>'manual_verification','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'merchant_docs_required','official_source'=>'https://cbl.gov.ly/electronic-payment/'],
        ],

        // CBL-licensed card/mobile-banking processors. Public directory proves
        // activity/licensing; merchant API endpoints are contract-specific.
        [
            'code'=>'tadawul_cards','name'=>'بطاقات محلية عبر تداول','type'=>'api','schema'=>'card_external','sort_order'=>170,
            'instructions'=>'تُفعّل بعد التعاقد مع تداول وإدخال معرّف التاجر ورابط الدفع من وثائق التاجر.',
            'config'=>['provider_name'=>'شركة تداول للتقنية','official_activity'=>'معالج إصدار وقبول البطاقات المحلية','integration_mode'=>'external_link','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'merchant_docs_required','official_source'=>'https://cbl.gov.ly/electronic-payment/'],
        ],
        [
            'code'=>'tafani_cards','name'=>'قبول البطاقات المحلية عبر تفاني','type'=>'api','schema'=>'card_external','sort_order'=>180,
            'instructions'=>'تُفعّل بعد التعاقد مع تفاني وإدخال بيانات التاجر ورابط الدفع الرسمي.',
            'config'=>['provider_name'=>'شركة تفاني للاتصالات والتقنية','official_activity'=>'معالج قبول بطاقات محلية','integration_mode'=>'external_link','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'merchant_docs_required','official_source'=>'https://cbl.gov.ly/electronic-payment/'],
        ],
        [
            'code'=>'obour_cards','name'=>'البطاقات المحلية عبر عبور','type'=>'api','schema'=>'card_external','sort_order'=>190,
            'instructions'=>'تُفعّل بعد التعاقد مع عبور وإدخال بيانات التاجر ورابط الدفع الرسمي.',
            'config'=>['provider_name'=>'شركة عبور لحلول الدفع الإلكتروني','official_activity'=>'معالج إصدار وقبول البطاقات المحلية','integration_mode'=>'external_link','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'merchant_docs_required','official_source'=>'https://cbl.gov.ly/electronic-payment/'],
        ],
        [
            'code'=>'masarat_mobile_cards','name'=>'مسارات - الهاتف المحمول والبطاقات المحلية','type'=>'api','schema'=>'card_external','sort_order'=>200,
            'instructions'=>'تُفعّل بعد التعاقد مع مسارات وإدخال بيانات التاجر/المصرف ورابط الدفع المعتمد.',
            'config'=>['provider_name'=>'شركة مسارات لتقنية المعلومات','official_activity'=>'الخدمات المصرفية عبر الهاتف المحمول ومعالج إصدار بطاقات محلية','integration_mode'=>'external_link','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'merchant_docs_required','official_source'=>'https://cbl.gov.ly/electronic-payment/'],
        ],
        [
            'code'=>'ithmar_cards','name'=>'البطاقات المحلية عبر إثمار','type'=>'api','schema'=>'card_external','sort_order'=>210,
            'instructions'=>'تُفعّل بعد التعاقد مع إثمار وإدخال بيانات التاجر/المعالج الرسمية.',
            'config'=>['provider_name'=>'شركة إثمار للدفع الإلكتروني','official_activity'=>'معالج إصدار بطاقات محلية','integration_mode'=>'external_link','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'merchant_docs_required','official_source'=>'https://cbl.gov.ly/electronic-payment/'],
        ],
        [
            'code'=>'moamalat_cards','name'=>'البطاقات عبر معاملات','type'=>'api','schema'=>'card_external','sort_order'=>220,
            'instructions'=>'تُفعّل بعد التعاقد مع معاملات وإدخال معرّف التاجر ورابط الدفع من وثائق التاجر.',
            'config'=>['provider_name'=>'شركة معاملات','official_activity'=>'معالج إصدار وقبول بطاقات','integration_mode'=>'external_link','proof_mode'=>'reference_or_receipt','currency'=>'LYD','availability'=>'online','documentation_status'=>'merchant_docs_required','official_source'=>'https://cbl.gov.ly/electronic-payment/'],
        ],
    ],
];
