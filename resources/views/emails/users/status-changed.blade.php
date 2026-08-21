<x-mail::message>
# Hello {{ $user->first_name }},

@if ($user->status === 'active')
Great news! Your account has been reactivated. You can now log in and access all features.
@else
Your account status has been changed to inactive. You currently cannot access system features.

If you have any questions, please contact system administration.
@endif


Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
