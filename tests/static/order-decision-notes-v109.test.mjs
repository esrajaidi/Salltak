import fs from 'node:fs';

const failures = [];
const workflow = fs.readFileSync('app/Services/OrderWorkflowService.php', 'utf8');
const controller = fs.readFileSync('app/Http/Controllers/Admin/OrderController.php', 'utf8');
const adminView = fs.readFileSync('resources/views/admin/orders/show.blade.php', 'utf8');
const settingsController = fs.readFileSync('app/Http/Controllers/Admin/SettingController.php', 'utf8');
const settingsView = fs.readFileSync('resources/views/admin/settings/edit.blade.php', 'utf8');

for (const token of [
  'resolveVisibility(',
  'requiresReason(',
  "string $visibility = 'auto'",
  "['rejected', 'cancelled', 'needs_customer_action']",
]) {
  if (!workflow.includes(token)) failures.push(`workflow missing ${token}`);
}

for (const token of [
  "'decision_note'=>['nullable','string','max:1500']",
  "$data['decision_note']??'تم قبول الطلب وتم اعتماد المنتجات والسعر النهائي.'",
]) {
  if (!controller.includes(token)) failures.push(`controller missing ${token}`);
}

if (!adminView.includes('ملاحظة القبول للعميل (اختيارية)')) failures.push('approval form missing customer decision note');
if (!adminView.includes('يحدد النظام ظهور الملاحظة تلقائيًا حسب نوع الإجراء')) failures.push('status form missing automatic visibility guidance');

if (!fs.existsSync('app/Services/CustomerOrderEmailNotifier.php')) failures.push('missing CustomerOrderEmailNotifier service');
if (!settingsController.includes('notify_email_customer_updates')) failures.push('settings controller missing customer update toggle');
if (!settingsView.includes('إشعارات العميل بحالة الطلب')) failures.push('settings UI missing customer update toggle');

if (failures.length) {
  console.error('FAIL V10.9 order decision notes:', failures.join('; '));
  process.exit(1);
}
console.log('PASS V10.9 order decision note checks');
