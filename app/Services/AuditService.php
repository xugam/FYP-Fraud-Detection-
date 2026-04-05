<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AuditService
{
    public function log(string $event, int $userId, array $context = []): void
    {
        Log::channel('audit')->info($event, array_merge([
            'user_id'    => $userId,
            'timestamp'  => now()->toIso8601String(),
        ], $context));
    }
}
