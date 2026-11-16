<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Integrations\WebsiteLeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebsiteLeadController extends Controller
{
    public function __construct(private readonly WebsiteLeadService $website) {}

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'full_name' => ['nullable', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:191'],
            'message' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'campaign' => ['nullable', 'string', 'max:191'],
            'utm_campaign' => ['nullable', 'string', 'max:191'],
            'utm_source' => ['nullable', 'string', 'max:191'],
            'utm_medium' => ['nullable', 'string', 'max:191'],
            'page_url' => ['nullable', 'string', 'max:500'],
            'product' => ['nullable', 'string', 'max:100'],
            'consent' => ['nullable'],
            'consent_contact' => ['nullable'],
            'idempotency_key' => ['nullable', 'string', 'max:191'],
            'external_id' => ['nullable', 'string', 'max:191'],
            // Honeypots — must stay empty
            'website_url' => ['nullable', 'string', 'max:0'],
            'company_fax' => ['nullable', 'string', 'max:0'],
            'api_key' => ['nullable', 'string', 'max:191'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (blank($data['phone'] ?? null) && blank($data['email'] ?? null) && blank($data['first_name'] ?? null) && blank($data['full_name'] ?? null)) {
            // Allow empty in sandbox only — service will inject sample; otherwise reject.
            if ($this->website->isLiveConfigured()) {
                return response()->json([
                    'message' => 'Provide at least a phone, email, or name.',
                ], 422);
            }
        }

        $event = $this->website->handleSubmission(
            $data,
            $request->headers->all(),
            queue: config('queue.default') !== 'sync'
        );

        if ($event->status === 'rejected') {
            $code = str_contains((string) $event->last_error, 'spam') ? 422 : 401;

            return response()->json([
                'message' => $event->last_error ?: 'Rejected',
                'status' => $event->status,
            ], $code);
        }

        $idempotentReplay = (bool) $event->getAttribute('idempotent_replay');

        return response()->json([
            'message' => $event->used_sandbox
                ? 'Lead accepted in sandbox mode (integration pending configuration).'
                : 'Lead accepted.',
            'status' => $event->status,
            'webhook_event_id' => $event->id,
            'lead_id' => $event->lead_id,
            'sandbox' => (bool) $event->used_sandbox,
            'mode' => $event->used_sandbox ? 'sandbox_pending_configuration' : 'live',
            'idempotent_replay' => $idempotentReplay,
        ], $idempotentReplay || $event->status === 'duplicate' ? 200 : 201);
    }
}
