<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * OTA Update Demo Model
 * ─────────────────────
 * Always reads/writes against the tenant connection.
 * This table only exists AFTER the tenant applies the OTA update,
 * which is exactly what makes it a useful end-to-end test.
 */
class OtaTestNote extends Model
{
    protected $connection = 'tenant';

    protected $table = 'ota_test_notes';

    protected $fillable = [
        'title',
        'body',
        'created_by',
    ];
}
