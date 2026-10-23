<?php

declare(strict_types=1);

namespace App\Models\Frontend;

use App\Traits\HasSchemaTable;

use App\Models\Frontend\Order\OrderLog as BaseOrderLog;

class OrderLog extends BaseOrderLog
{
    use HasSchemaTable;

}
