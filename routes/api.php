<?php

use App\Http\Controllers\Api\InstagramWebhookController;
use App\Http\Controllers\Api\MetaWebhookController;
use App\Http\Controllers\Api\WebsiteLeadController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Middleware\ThrottleWebsiteLeads;
use Illuminate\Support\Facades\Route;

/*
| Digital lead ingress (Phase 4).
| These are Phoenix CRM receiver endpoints. Outbound calls use official
| Meta / WhatsApp Graph API URLs only (see integration services).
*/

Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive']);

Route::get('/webhooks/meta', [MetaWebhookController::class, 'verify']);
Route::post('/webhooks/meta', [MetaWebhookController::class, 'receive']);

Route::get('/webhooks/instagram', [InstagramWebhookController::class, 'verify']);
Route::post('/webhooks/instagram', [InstagramWebhookController::class, 'receive']);

Route::post('/leads/website', [WebsiteLeadController::class, 'store'])
    ->middleware(ThrottleWebsiteLeads::class);
