<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSchemaTable;

abstract class BaseModel extends Model
{
    use HasSchemaTable;
}
