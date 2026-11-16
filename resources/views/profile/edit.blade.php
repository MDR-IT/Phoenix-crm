<x-app-layout>
    <x-slot name="header">Profile</x-slot>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="pc-panel mb-3">
                @include('profile.partials.update-profile-information-form')
            </div>
            <div class="pc-panel mb-3">
                @include('profile.partials.update-password-form')
            </div>
            <div class="pc-panel">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
