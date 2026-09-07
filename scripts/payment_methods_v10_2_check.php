<?php
// Compatibility entry point: V10.2's payment catalog evolved into V10.3.
// Keep this filename so old local verification commands still execute the
// current payment checks instead of reporting a false historical failure.
require __DIR__.'/payment_methods_v10_3_check.php';
