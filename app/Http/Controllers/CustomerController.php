<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\County;
use App\Models\Customer;
use App\Services\DuplicateLeadDetector;
use App\Support\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        private readonly DuplicateLeadDetector $duplicateDetector,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->with('county')
            ->withCount('leads')
            ->when($request->string('q')->toString(), function ($query, string $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('phone_normalized', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.create', [
            'counties' => County::query()->where('is_active', true)->orderBy('name')->get(),
            'genders' => config('phoenix.genders'),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        $data = $request->validated();
        $duplicates = $this->duplicateDetector->findPossibleCustomerDuplicates(
            $data['phone'] ?? null,
            $data['phone_alt'] ?? null,
            $data['email'] ?? null
        );

        $customer = Customer::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'full_name' => trim($data['first_name'].' '.($data['last_name'] ?? '')),
            'phone' => $data['phone'],
            'phone_normalized' => PhoneNormalizer::normalize($data['phone']),
            'phone_alt' => $data['phone_alt'] ?? null,
            'phone_alt_normalized' => PhoneNormalizer::normalize($data['phone_alt'] ?? null),
            'email' => $data['email'] ?? null,
            'gender' => $data['gender'] ?? null,
            'county_id' => $data['county_id'] ?? null,
            'town' => $data['town'] ?? null,
            'consent_marketing' => $request->boolean('consent_marketing'),
            'consent_at' => $request->boolean('consent_marketing') ? now() : null,
            'notes' => $data['notes'] ?? null,
        ]);

        $redirect = redirect()->route('customers.show', $customer)
            ->with('success', 'Customer registered successfully.');

        if ($duplicates->isNotEmpty()) {
            $redirect->with('warning', 'Possible duplicate customers were found for this phone/email. Review carefully.');
        }

        return $redirect;
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $customer->load([
            'county',
            'noteEntries.user',
            'leads' => fn ($q) => $q->with(['assignee', 'product', 'leadSource'])->latest(),
        ]);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.edit', [
            'customer' => $customer,
            'counties' => County::query()->where('is_active', true)->orderBy('name')->get(),
            'genders' => config('phoenix.genders'),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $data = $request->validated();

        $customer->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'full_name' => trim($data['first_name'].' '.($data['last_name'] ?? '')),
            'phone' => $data['phone'],
            'phone_normalized' => PhoneNormalizer::normalize($data['phone']),
            'phone_alt' => $data['phone_alt'] ?? null,
            'phone_alt_normalized' => PhoneNormalizer::normalize($data['phone_alt'] ?? null),
            'email' => $data['email'] ?? null,
            'gender' => $data['gender'] ?? null,
            'county_id' => $data['county_id'] ?? null,
            'town' => $data['town'] ?? null,
            'consent_marketing' => $request->boolean('consent_marketing'),
            'consent_at' => $request->boolean('consent_marketing')
                ? ($customer->consent_at ?? now())
                : $customer->consent_at,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }
}
