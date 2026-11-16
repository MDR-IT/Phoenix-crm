<?php

namespace App\Http\Controllers;

use App\Http\Requests\Note\StoreNoteRequest;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Note;
use App\Services\NoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct(
        private readonly NoteService $noteService,
    ) {}

    public function storeForLead(StoreNoteRequest $request, Lead $lead): RedirectResponse
    {
        $this->authorize('view', $lead);
        $this->authorize('create', Note::class);

        $this->noteService->create($lead, $request->validated(), $request->user());

        return back()->with('success', 'Note added to lead timeline.');
    }

    public function storeForCustomer(StoreNoteRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('create', Note::class);

        $this->noteService->create($customer, $request->validated(), $request->user());

        return back()->with('success', 'Note added to customer.');
    }

    public function destroy(Request $request, Note $note): RedirectResponse
    {
        $this->authorize('delete', $note);

        $this->noteService->delete($note);

        return back()->with('success', 'Note removed.');
    }
}
