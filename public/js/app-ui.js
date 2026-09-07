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

    const boot = () => { reveal(); flash(); confirmations(); };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
    else boot();
})();
