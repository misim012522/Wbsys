<?php

namespace App\Models\Concerns;

trait UsesCentralConnection
{
    public function getConnectionName(): ?string
    {
        if (app()->environment('testing')) {
            return config('database.default');
        }

        return 'central';
    }
}
