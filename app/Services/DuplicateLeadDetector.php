<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Collection;

class DuplicateLeadDetector
{
    /**
     * @return Collection<int, Lead>
     */
    public function findPossibleDuplicates(?string $phone, ?string $phoneAlt = null, ?string $email = null, ?int $excludeLeadId = null): Collection
    {
        $phones = collect([
            PhoneNormalizer::normalize($phone),
            PhoneNormalizer::normalize($phoneAlt),
        ])->filter()->unique()->values();

        $email = $email ? strtolower(trim($email)) : null;

        if ($phones->isEmpty() && blank($email)) {
            return collect();
        }

        $customerIds = Customer::query()
            ->when($phones->isNotEmpty(), function ($query) use ($phones) {
                $query->where(function ($inner) use ($phones) {
                    $inner->whereIn('phone_normalized', $phones)
                        ->orWhereIn('phone_alt_normalized', $phones);
                });
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->when($email, function ($query) use ($email, $phones) {
                if ($phones->isEmpty()) {
                    $query->orWhereRaw('LOWER(email) = ?', [$email]);
                } else {
                    $query->orWhereRaw('LOWER(email) = ?', [$email]);
                }
            })
            ->pluck('id');

        if ($customerIds->isEmpty() && blank($email)) {
            return collect();
        }

        return Lead::query()
            ->with(['customer', 'assignee', 'product'])
            ->when($excludeLeadId, fn ($q) => $q->where('id', '!=', $excludeLeadId))
            ->where(function ($query) use ($customerIds, $email) {
                if ($customerIds->isNotEmpty()) {
                    $query->whereIn('customer_id', $customerIds);
                }
                if ($email) {
                    $query->orWhereHas('customer', fn ($c) => $c->whereRaw('LOWER(email) = ?', [$email]));
                }
            })
            ->latest()
            ->limit(25)
            ->get();
    }

    /**
     * @return Collection<int, Customer>
     */
    public function findPossibleCustomerDuplicates(?string $phone, ?string $phoneAlt = null, ?string $email = null, ?int $excludeCustomerId = null): Collection
    {
        $phones = collect([
            PhoneNormalizer::normalize($phone),
            PhoneNormalizer::normalize($phoneAlt),
        ])->filter()->unique()->values();

        $email = $email ? strtolower(trim($email)) : null;

        if ($phones->isEmpty() && blank($email)) {
            return collect();
        }

        return Customer::query()
            ->when($excludeCustomerId, fn ($q) => $q->where('id', '!=', $excludeCustomerId))
            ->where(function ($query) use ($phones, $email) {
                if ($phones->isNotEmpty()) {
                    $query->where(function ($inner) use ($phones) {
                        $inner->whereIn('phone_normalized', $phones)
                            ->orWhereIn('phone_alt_normalized', $phones);
                    });
                }
                if ($email) {
                    $query->orWhereRaw('LOWER(email) = ?', [$email]);
                }
            })
            ->latest()
            ->limit(25)
            ->get();
    }
}
