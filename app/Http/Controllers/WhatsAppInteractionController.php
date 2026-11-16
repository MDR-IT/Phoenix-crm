<?php

namespace App\Http\Controllers;

use App\Models\ChannelInteraction;
use Illuminate\Http\Request;

class WhatsAppInteractionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            auth()->user()?->can('integrations.view') || auth()->user()?->can('leads.view') || auth()->user()?->can('leads.manage'),
            403
        );

        $query = ChannelInteraction::query()
            ->with(['lead.customer', 'customer'])
            ->where('channel', 'whatsapp')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('body', 'like', '%'.$search.'%')
                    ->orWhere('sender_handle', 'like', '%'.$search.'%')
                    ->orWhere('external_id', 'like', '%'.$search.'%');
            });
        }

        return view('integrations.whatsapp-interactions', [
            'interactions' => $query->paginate(25)->withQueryString(),
        ]);
    }
}
