<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuthAuditService
{
    private function ip(Request $request): ?string
    {
        return $request->ip();
    }

    private function userAgent(Request $request): ?string
    {
        return $request->userAgent();
    }

    public function log(
        ?User $user,
        string $action,
        Request $request,
        array $metadata = []
    ): ActivityLog {

    
        return ActivityLog::create([
            'user_id' => $user->getAttributes()['id'],
            'action' => $action,
            'ip_address' => $this->ip($request),
            'user_agent' => $this->userAgent($request),
            'metadata' => $metadata ?: null,
        ]);
    }
}

