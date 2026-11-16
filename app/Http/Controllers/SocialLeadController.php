<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;

class SocialLeadController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            auth()->user()?->can('integrations.view') || auth()->user()?->can('leads.view') || auth()->user()?->can('leads.manage'),
            403
        );

        $channels = ['facebook', 'instagram', 'messenger', 'whatsapp', 'website'];
        $channel = $request->string('channel')->toString();

        $query = Lead::query()
            ->with(['customer', 'assignee', 'leadSource'])
            ->whereIn('channel', $channels)
            ->orderByDesc('id');

        if ($channel && in_array($channel, $channels, true)) {
            $query->where('channel', $channel);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', '%'.$search.'%')
                    ->orWhere('campaign', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', function ($c) use ($search) {
                        $c->where('full_name', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }

        return view('integrations.social-leads', [
            'leads' => $query->paginate(25)->withQueryString(),
            'channel' => $channel,
            'channels' => $channels,
        ]);
    }
}
