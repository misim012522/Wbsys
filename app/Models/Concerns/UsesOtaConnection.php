<?php

namespace App\Models\Concerns;

trait UsesOtaConnection
{
    public function getConnectionName(): ?string
    {
        if (app()->environment('testing')) {
            return config('database.default');
        }

        return 'ota';
    }
}
