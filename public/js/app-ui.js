(() => {
    document.documentElement.classList.add('js-enabled');

    const reveal = () => {
        const nodes = document.querySelectorAll('.reveal');
        if (!nodes.length) return;
        if (!('IntersectionObserver' in window)) {
            nodes.forEach(node => node.classList.add('is-visible'));
            return;
        }
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0, rootMargin: '0px 0px -20px 0px' });
        nodes.forEach(node => observer.observe(node));
    };

    const flash = () => {
        const holder = document.getElementById('swal-flash');
        if (!holder) return;
        const success = holder.dataset.swalSuccess || '';
        let errors = [];
        try { errors = JSON.parse(holder.dataset.swalErrors || '[]'); } catch (_) { errors = []; }

        if (success) {
            if (window.Swal) {
                Swal.fire({toast:true,position:'top-start',icon:'success',title:success,showConfirmButton:false,timer:3400,timerProgressBar:true});
            } else {
                console.info(success);
            }
        } else if (errors.length) {
            const html = `<div class="text-end"><ul class="mb-0">${errors.map(error => `<li>${String(error).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}</li>`).join('')}</ul></div>`;
            if (window.Swal) Swal.fire({icon:'error',title:'راجع البيانات',html,confirmButtonText:'حسنًا'});
            else alert(errors.join('\n'));
        }
    };

    const confirmations = () => {
        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-confirm]');
            if (!form || form.dataset.confirmed === '1') return;
            event.preventDefault();

            const title = form.dataset.confirmTitle || 'تأكيد العملية';
            const text = form.dataset.confirmText || form.dataset.confirm || 'هل أنت متأكد من تنفيذ هذه العملية؟';
            const icon = form.dataset.confirmIcon || 'warning';
            const confirmText = form.dataset.confirmButton || 'نعم، تنفيذ';

            const submit = () => {
                form.dataset.confirmed = '1';
                if (event.submitter?.name) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden'; hidden.name = event.submitter.name; hidden.value = event.submitter.value;
                    form.appendChild(hidden);
                }
                form.submit();
            };

            if (window.Swal) {
                Swal.fire({icon,title,text,showCancelButton:true,confirmButtonText:confirmText,cancelButtonText:'إلغاء',reverseButtons:true})
                    .then(result => { if (result.isConfirmed) submit(); });
            } else if (window.confirm(text)) submit();
        });
    };


    const cartImportLoading = () => {
        const forms = document.querySelectorAll('form[data-cart-import]');
        const overlay = document.getElementById('cart-import-overlay');
        if (!forms.length || !overlay) return;
        const title = overlay.querySelector('[data-import-title]');
        const message = overlay.querySelector('[data-import-message]');
        const messages = [
            ['استنا شوية... جاري جلب السلة','لا تقفلي الصفحة، بنجيب المنتجات الحقيقية ونجهزها للعرض.'],
            ['جاري قراءة المنتجات والأسعار','نتأكد من أسماء المنتجات والأسعار الأصلية بالدولار.'],
            ['نتحقق من الصور والمقاسات والألوان','نرتب تفاصيل كل منتج بدون ما نغيّر السعر المستورد.'],
            ['قربنا نكمل... يتم تجهيز السلة للعرض','باقي خطوة بسيطة وتظهر لك السلة كاملة.'],
        ];
        forms.forEach(form => form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.querySelector('.submit-label')?.replaceChildren(document.createTextNode('جاري الجلب...'));
                button.querySelector('.spinner-border')?.classList.remove('d-none');
            }
            overlay.classList.add('is-open');
            overlay.setAttribute('aria-hidden','false');
            document.body.classList.add('is-importing-cart');
            let index = 0;
            window.setInterval(() => {
                index = (index + 1) % messages.length;
                if (title) title.textContent = messages[index][0];
                if (message) message.textContent = messages[index][1];
            }, 2400);
        }));
    };

    const responsiveTables = () => {
        document.querySelectorAll('.admin-main .table-modern').forEach((table) => {
            const labels = Array.from(table.querySelectorAll('thead th')).map((cell) => cell.textContent.trim());
            table.querySelectorAll('tbody tr').forEach((row) => {
                Array.from(row.children).forEach((cell, index) => {
                    if (cell.hasAttribute('colspan') || cell.dataset.label) return;
                    const label = labels[index] || '';
                    if (label) cell.setAttribute('data-label', label);
                });
            });
        });
    };

    const boot = () => { reveal(); flash(); confirmations(); cartImportLoading(); responsiveTables(); };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
    else boot();
})();
