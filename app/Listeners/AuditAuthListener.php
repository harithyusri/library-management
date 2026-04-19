<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Support\Facades\Request;

class AuditAuthListener
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $auditData = [
            'user_type' => \App\Models\User::class,
            'user_id' => null,
            'event' => '',
            'auditable_type' => \App\Models\User::class,
            'auditable_id' => null,
            'old_values' => [],
            'new_values' => [],
            'url' => Request::fullUrl(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'tags' => 'auth',
        ];

        if ($event instanceof Login) {
            $auditData['user_id'] = $event->user->id;
            $auditData['auditable_id'] = $event->user->id;
            $auditData['event'] = 'login';
        } elseif ($event instanceof Logout) {
            if ($event->user) {
                $auditData['user_id'] = $event->user->id;
                $auditData['auditable_id'] = $event->user->id;
            }
            $auditData['event'] = 'logout';
        } elseif ($event instanceof Failed) {
            if ($event->user) {
                $auditData['user_id'] = $event->user->id;
                $auditData['auditable_id'] = $event->user->id;
            }
            $auditData['event'] = 'failed_login';
            $auditData['new_values'] = ['email' => $event->credentials['email'] ?? 'unknown'];
        } else {
            return;
        }

        Audit::create($auditData);
    }
}
