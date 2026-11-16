<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\LeadSource;
use App\Models\Product;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CrmLookupSeeder extends Seeder
{
    public function run(): void
    {
        $counties = [
            ['name' => 'Nairobi', 'code' => 'NRB', 'region' => 'Nairobi'],
            ['name' => 'Mombasa', 'code' => 'MSA', 'region' => 'Coast'],
            ['name' => 'Kisumu', 'code' => 'KSM', 'region' => 'Nyanza'],
            ['name' => 'Nakuru', 'code' => 'NKR', 'region' => 'Rift Valley'],
            ['name' => 'Kiambu', 'code' => 'KBU', 'region' => 'Central'],
            ['name' => 'Machakos', 'code' => 'MKS', 'region' => 'Eastern'],
            ['name' => 'Uasin Gishu', 'code' => 'UG', 'region' => 'Rift Valley'],
            ['name' => 'Kilifi', 'code' => 'KLF', 'region' => 'Coast'],
            ['name' => 'Kakamega', 'code' => 'KKG', 'region' => 'Western'],
            ['name' => 'Nyeri', 'code' => 'NYR', 'region' => 'Central'],
        ];

        foreach ($counties as $county) {
            County::query()->updateOrCreate(['name' => $county['name']], $county + ['is_active' => true]);
        }

        $products = [
            ['name' => 'Personal Loan', 'code' => 'PL'],
            ['name' => 'Business Loan', 'code' => 'BL'],
            ['name' => 'Asset Finance', 'code' => 'AF'],
            ['name' => 'Salary Advance', 'code' => 'SA'],
            ['name' => 'Logbook Loan', 'code' => 'LL'],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(['code' => $product['code']], $product + ['is_active' => true]);
        }

        $sources = [
            ['name' => 'Manual Entry', 'code' => 'manual'],
            ['name' => 'Website', 'code' => 'website'],
            ['name' => 'WhatsApp', 'code' => 'whatsapp'],
            ['name' => 'Facebook Lead Ads', 'code' => 'facebook'],
            ['name' => 'Instagram', 'code' => 'instagram'],
            ['name' => 'Phone / Yeastar', 'code' => 'phone'],
            ['name' => 'Walk-in', 'code' => 'walkin'],
            ['name' => 'Referral', 'code' => 'referral'],
            ['name' => 'Campaign', 'code' => 'campaign'],
        ];

        foreach ($sources as $source) {
            LeadSource::query()->updateOrCreate(['code' => $source['code']], $source + ['is_active' => true]);
        }

        $teams = [
            'Call Centre Team A',
            'Call Centre Team B',
            'Sales Nairobi',
            'Sales Coast',
        ];

        foreach ($teams as $name) {
            Team::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true]
            );
        }
    }
}
