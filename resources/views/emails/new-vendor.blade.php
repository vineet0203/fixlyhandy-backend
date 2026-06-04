{{-- resources/views/emails/admin/new-vendor.blade.php --}}
@component('mail::message')
# New Vendor Registration

Hello {{ $admin->first_name }},

A new vendor has registered on {{ config('app.name') }} platform.

## Vendor Details:
- **Business Name:** {{ $vendor->business_name }}
- **Business Type:** {{ $vendor->business_type ?? 'Not specified' }}
- **Email:** {{ $user->email }}
- **Phone:** {{ $vendor->mobile_number ?? 'Not provided' }}
- **Website:** {{ $vendor->website_name ?? 'Not provided' }}
- **Registration Date:** {{ $registrationDate }}

@component('mail::button', ['url' => $vendorUrl])
View Vendor Details
@endcomponent

You can also view all vendors in the [Admin Dashboard]({{ $adminDashboardUrl }}).

Thanks,<br>
{{ config('app.name') }} Team
@endcomponent