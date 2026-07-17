<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * خطأ في عملية مخزنية (رصيد غير كافٍ، نقل لأعلى غير مسموح، ...).
 * الرسالة عربية جاهزة للعرض للمستخدم.
 */
class WarehouseException extends RuntimeException
{
}
