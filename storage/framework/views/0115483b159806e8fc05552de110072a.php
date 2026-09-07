<?php $__env->startSection('title','مراجعة السلة'); ?>

<?php $__env->startSection('body'); ?>
<section class="page-section customer-page">
    <div class="container">
        <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4 reveal is-visible">
            <div>
                <div class="page-kicker">قبل الحفظ</div>
                <h1 class="page-heading">راجع سلتك</h1>
                <p class="page-subtitle">تأكد من المنتجات والكميات. السعر بالدينار الليبي محسوب حسب سعر الصرف الحالي.</p>
            </div>
            <a class="btn btn-ghost" href="<?php echo e(route('carts.create')); ?>">تغيير الرابط</a>
        </div>

        <div class="surface-card-elevated reveal p-3 p-md-4">
            <div class="alert <?php echo e($result->status === 'success' ? 'alert-success' : 'alert-warning'); ?> border-0 import-alert mb-3">
                <div class="import-alert-icon"><?php echo e($result->status === 'success' ? '✓' : '!'); ?></div>
                <div class="flex-grow-1">
                    <div class="fw-bold"><?php echo e($result->status === 'success' ? 'تم جلب السلة' : 'تحتاج مراجعة'); ?></div>
                    <div class="small mt-1"><?php echo e($result->message); ?></div>
                    <?php if($result->status === 'success'): ?><div class="small mt-1">تم العثور على <strong><?php echo e(count($result->items)); ?></strong> منتج/منتجات من السلة.</div><?php endif; ?>
                </div>
            </div>

            <?php if(!empty($result->meta['group_id'])): ?>
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 p-3 rounded-4 border bg-light mb-4">
                    <div>
                        <div class="small text-secondary">SHEIN Share Cart</div>
                        <div class="fw-bold mt-1">المجموعة <span class="ltr d-inline-block">#<?php echo e($result->meta['group_id']); ?></span></div>
                        <?php if(!empty($result->meta['local_country'])): ?><div class="small text-secondary mt-1">بلد السلة: <?php echo e($result->meta['local_country']); ?></div><?php endif; ?>
                    </div>
                    <form method="POST" action="<?php echo e(route('carts.analyze')); ?>" class="m-0">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="source_url" value="<?php echo e($sourceUrl); ?>">
                        <button class="btn btn-outline-primary btn-sm" type="submit">إعادة جلب السلة</button>
                    </form>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('carts.store')); ?>" id="cartForm">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="source_url" value="<?php echo e($sourceUrl); ?>">
                <input type="hidden" name="store_id" value="<?php echo e($store?->id); ?>">
                <input type="hidden" name="source_currency" value="<?php echo e($currency); ?>">
                <input type="hidden" name="import_status" value="<?php echo e($result->status); ?>">
                <input type="hidden" name="import_message" value="<?php echo e($result->message); ?>">

                <div class="row g-2 g-md-3 mb-4">
                    <div class="col-6 col-lg-3"><div class="summary-tile"><div class="summary-label">المتجر</div><div class="summary-value"><?php echo e($store?->name ?? 'موقع خارجي'); ?></div></div></div>
                    <div class="col-6 col-lg-3"><div class="summary-tile"><div class="summary-label">العملة</div><div class="summary-value ltr text-end"><?php echo e($currency); ?></div></div></div>
                    <div class="col-6 col-lg-3"><div class="summary-tile"><div class="summary-label">سعر الصرف</div><div class="summary-value"><span class="ltr">1 <?php echo e($currency); ?></span> = <?php echo e(number_format((float)$rate, 4)); ?> د.ل</div></div></div>
                    <div class="col-6 col-lg-3"><div class="summary-tile"><div class="summary-label">عدد المنتجات</div><div class="summary-value" id="itemsCount"><?php echo e(count($result->items) ?: 1); ?></div></div></div>
                </div>

                <div class="cart-toolbar d-flex align-items-center justify-content-between gap-3 mb-1">
                    <div><h2 class="h5 fw-bold mb-1">منتجات السلة</h2><div class="small text-secondary">السعر الأصلي والسعر بالدينار ظاهرين معًا.</div></div>
                    <button class="btn btn-soft btn-sm" id="addItem" type="button">+ إضافة منتج</button>
                </div>

                <div id="items">
                    <?php ($rows = $result->items ?: [[
                        'name'=>'', 'product_url'=>'', 'image_url'=>'', 'external_id'=>'',
                        'variant'=>'', 'color'=>'', 'size'=>'', 'quantity'=>1, 'unit_price_original'=>0
                    ]]); ?>

                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <article class="product-editor" data-row>
                            <div class="row g-3 align-items-start">
                                <div class="col-auto">
                                    <?php if(!empty($item['image_url'])): ?>
                                        <img class="product-image" src="<?php echo e($item['image_url']); ?>" alt="<?php echo e($item['name'] ?? 'منتج'); ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="product-image product-image-placeholder">بدون صورة</div>
                                    <?php endif; ?>
                                </div>

                                <div class="col min-w-0">
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                        <div class="small text-secondary fw-bold">المنتج <?php echo e($i + 1); ?></div>
                                        <button class="btn btn-danger-soft btn-sm remove" type="button">حذف</button>
                                    </div>
                                    <label class="visually-hidden" for="item-name-<?php echo e($i); ?>">اسم المنتج</label>
                                    <input id="item-name-<?php echo e($i); ?>" class="form-control product-title-input" name="items[<?php echo e($i); ?>][name]" value="<?php echo e($item['name'] ?? ''); ?>" placeholder="اسم المنتج" required>

                                    <input type="hidden" name="items[<?php echo e($i); ?>][external_id]" value="<?php echo e($item['external_id'] ?? ''); ?>">
                                    <input type="hidden" name="items[<?php echo e($i); ?>][product_url]" value="<?php echo e($item['product_url'] ?? ''); ?>">
                                    <input type="hidden" name="items[<?php echo e($i); ?>][image_url]" value="<?php echo e($item['image_url'] ?? ''); ?>">

                                    <div class="row g-2 mt-1 product-meta-inputs">
                                        <div class="col-6 col-md-4"><input class="form-control" name="items[<?php echo e($i); ?>][color]" value="<?php echo e($item['color'] ?? ''); ?>" placeholder="اللون"></div>
                                        <div class="col-6 col-md-4"><input class="form-control" name="items[<?php echo e($i); ?>][size]" value="<?php echo e($item['size'] ?? ''); ?>" placeholder="المقاس"></div>
                                        <div class="col-12 col-md-4"><input class="form-control" name="items[<?php echo e($i); ?>][variant]" value="<?php echo e($item['variant'] ?? ''); ?>" placeholder="SKU / الخيار"></div>
                                    </div>

                                    <?php if(!empty($item['external_id'])): ?><div class="small text-secondary mt-2">ID: <code class="ltr"><?php echo e($item['external_id']); ?></code></div><?php endif; ?>
                                </div>

                                <div class="col-12 col-lg-5 col-xl-4">
                                    <div class="product-price-panel">
                                        <div class="price-pair mb-2">
                                            <div class="price-box">
                                                <span class="price-label">سعر القطعة</span>
                                                <div class="d-flex align-items-center gap-1 ltr">
                                                    <input class="form-control form-control-sm price border-0 p-0 shadow-none fw-bold" type="number" step="0.01" min="0" name="items[<?php echo e($i); ?>][unit_price_original]" value="<?php echo e($item['unit_price_original'] ?? 0); ?>" required>
                                                    <strong><?php echo e($currency); ?></strong>
                                                </div>
                                            </div>
                                            <div class="price-box lyd">
                                                <span class="price-label">بالدينار الليبي</span>
                                                <span class="price-value unit-lyd">0.00 د.ل</span>
                                            </div>
                                        </div>

                                        <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-end justify-content-between gap-2">
                                            <div>
                                                <div class="small text-secondary fw-bold mb-1">الكمية</div>
                                                <div class="quantity-control">
                                                    <button class="qty-btn qty-minus" type="button" aria-label="نقص الكمية">−</button>
                                                    <input class="form-control qty" type="number" min="1" max="999" name="items[<?php echo e($i); ?>][quantity]" value="<?php echo e($item['quantity'] ?? 1); ?>" required>
                                                    <button class="qty-btn qty-plus" type="button" aria-label="زيادة الكمية">+</button>
                                                </div>
                                            </div>
                                            <div class="text-sm-end">
                                                <div class="small text-secondary fw-bold">إجمالي المنتج</div>
                                                <div class="fw-bold ltr line-total-original">0.00 <?php echo e($currency); ?></div>
                                                <div class="fw-bold text-primary line-total-lyd">0.00 د.ل</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <div class="row g-3 mt-3 align-items-stretch">
                    <div class="col-lg-5 ms-lg-auto">
                        <div class="cart-total-card h-100">
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-2"><span class="summary-label">إجمالي السلة بالعملة الأصلية</span><strong class="ltr" id="subtotal">0.00 <?php echo e($currency); ?></strong></div>
                            <div class="d-flex align-items-end justify-content-between gap-3"><div><div class="summary-label">الإجمالي بالدينار الليبي</div><div class="small opacity-75 mt-1">حسب سعر الصرف <?php echo e(number_format((float)$rate,4)); ?></div></div><div class="lyd-grand text-nowrap" id="lyd">0.00 د.ل</div></div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 mt-4">
                    <a class="btn btn-ghost" href="<?php echo e(route('carts.create')); ?>">إلغاء</a>
                    <button class="btn btn-primary px-4" type="submit">حفظ السلة</button>
                </div>
            </form>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const rate = <?php echo e((float)$rate); ?>;
const currency = <?php echo json_encode($currency, 15, 512) ?>;
let index = <?php echo e(count($rows)); ?>;

function money(value){ return (Number(value) || 0).toFixed(2); }

function calc(){
    let subtotal = 0;
    const rows = document.querySelectorAll('[data-row]');
    rows.forEach(row => {
        const price = Math.max(0, parseFloat(row.querySelector('.price')?.value) || 0);
        const qtyInput = row.querySelector('.qty');
        const qty = Math.max(1, parseInt(qtyInput?.value) || 1);
        if (qtyInput && Number(qtyInput.value) !== qty) qtyInput.value = qty;
        const line = price * qty;
        subtotal += line;
        row.querySelector('.unit-lyd').textContent = money(price * rate) + ' د.ل';
        row.querySelector('.line-total-original').textContent = money(line) + ' ' + currency;
        row.querySelector('.line-total-lyd').textContent = money(line * rate) + ' د.ل';
    });
    document.getElementById('subtotal').textContent = money(subtotal) + ' ' + currency;
    document.getElementById('lyd').textContent = money(subtotal * rate) + ' د.ل';
    document.getElementById('itemsCount').textContent = rows.length;
}

document.addEventListener('input', e => { if (e.target.matches('.price,.qty')) calc(); });

document.addEventListener('click', e => {
    const plus = e.target.closest('.qty-plus');
    const minus = e.target.closest('.qty-minus');
    if (plus || minus){
        const input = e.target.closest('.quantity-control').querySelector('.qty');
        let value = Math.max(1, parseInt(input.value) || 1);
        input.value = plus ? Math.min(999, value + 1) : Math.max(1, value - 1);
        calc();
        return;
    }
    const remove = e.target.closest('.remove');
    if (remove){
        const rows = document.querySelectorAll('[data-row]');
        if (rows.length <= 1) return;
        remove.closest('[data-row]').remove();
        calc();
    }
});

document.getElementById('addItem').addEventListener('click', () => {
    document.getElementById('items').insertAdjacentHTML('beforeend', `
        <article class="product-editor" data-row>
            <div class="row g-3 align-items-start">
                <div class="col-auto"><div class="product-image product-image-placeholder">بدون صورة</div></div>
                <div class="col min-w-0">
                    <div class="d-flex justify-content-between gap-2 mb-2"><div class="small text-secondary fw-bold">منتج جديد</div><button class="btn btn-danger-soft btn-sm remove" type="button">حذف</button></div>
                    <input class="form-control product-title-input" name="items[${index}][name]" placeholder="اسم المنتج" required>
                    <input type="hidden" name="items[${index}][external_id]" value=""><input type="hidden" name="items[${index}][product_url]" value=""><input type="hidden" name="items[${index}][image_url]" value="">
                    <div class="row g-2 mt-1 product-meta-inputs"><div class="col-6 col-md-4"><input class="form-control" name="items[${index}][color]" placeholder="اللون"></div><div class="col-6 col-md-4"><input class="form-control" name="items[${index}][size]" placeholder="المقاس"></div><div class="col-12 col-md-4"><input class="form-control" name="items[${index}][variant]" placeholder="SKU / الخيار"></div></div>
                </div>
                <div class="col-12 col-lg-5 col-xl-4"><div class="product-price-panel">
                    <div class="price-pair mb-2"><div class="price-box"><span class="price-label">سعر القطعة</span><div class="d-flex align-items-center gap-1 ltr"><input class="form-control form-control-sm price border-0 p-0 shadow-none fw-bold" type="number" step="0.01" min="0" name="items[${index}][unit_price_original]" value="0" required><strong>${currency}</strong></div></div><div class="price-box lyd"><span class="price-label">بالدينار الليبي</span><span class="price-value unit-lyd">0.00 د.ل</span></div></div>
                    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-end justify-content-between gap-2"><div><div class="small text-secondary fw-bold mb-1">الكمية</div><div class="quantity-control"><button class="qty-btn qty-minus" type="button">−</button><input class="form-control qty" type="number" min="1" max="999" name="items[${index}][quantity]" value="1" required><button class="qty-btn qty-plus" type="button">+</button></div></div><div class="text-sm-end"><div class="small text-secondary fw-bold">إجمالي المنتج</div><div class="fw-bold ltr line-total-original">0.00 ${currency}</div><div class="fw-bold text-primary line-total-lyd">0.00 د.ل</div></div></div>
                </div></div>
            </div>
        </article>`);
    index++; calc();
});

calc();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Salltak\resources\views/carts/preview.blade.php ENDPATH**/ ?>