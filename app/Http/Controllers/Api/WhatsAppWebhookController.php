<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Integrations\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsApp) {}

    public function verify(Request $request): Response
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode', ''));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge', ''));

        $result = $this->whatsApp->verifyChallenge($mode, $token, $challenge);
        if ($result === null) {
            return response('Forbidden', 403);
        }

        return response($result, 200)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request)
    {
        $event = $this->whatsApp->handleWebhook(
            $request->all(),
            $request->getContent() ?: json_encode($request->all()) ?: '',
            $request->headers->all(),
            queue: config('queue.default') !== 'sync'
        );

        return response()->json([
            'status' => $event->status,
            'webhook_event_id' => $event->id,
            'sandbox' => (bool) $event->used_sandbox,
            'mode' => $event->used_sandbox ? 'sandbox_pending_configuration' : 'live',
        ]);
    }
}
