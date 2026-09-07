import fs from 'node:fs';
let failures=[];
const layout=fs.readFileSync('resources/views/layouts/app.blade.php','utf8');
for (const token of ['sweetalert2@11','partials.notification-bell','data-swal-success','data-swal-errors']) if (!layout.includes(token)) failures.push(`layout missing ${token}`);
if (!fs.existsSync('resources/views/partials/notification-bell.blade.php')) failures.push('missing notification bell partial');
if (!fs.existsSync('resources/views/notifications/index.blade.php')) failures.push('missing notification inbox');
const js=fs.readFileSync('public/js/app-ui.js','utf8');
for (const token of ['window.Swal','data-confirm','Swal.fire']) if (!js.includes(token)) failures.push(`app-ui missing ${token}`);
const adminLayout=fs.readFileSync('resources/views/layouts/admin.blade.php','utf8');
if (!adminLayout.includes("route('logout')")) failures.push('admin layout missing logout action');
const provider=fs.readFileSync('app/Providers/AppServiceProvider.php','utf8');
for (const token of ['View::composer','navNotifications','unreadNotificationCount','Schema::hasTable']) if (!provider.includes(token)) failures.push(`provider missing ${token}`);
if (failures.length){console.error('FAIL UI shell:', failures.join('; ')); process.exit(1)}
console.log('PASS UI shell / SweetAlert / notifications checks');
