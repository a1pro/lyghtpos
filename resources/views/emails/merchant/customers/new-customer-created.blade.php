<x-mail::message>
# Your Account Details

You can log in using the following credentials:

**Email:** {{ $user->email }}
**Password:** {{ $password }}

Thanks,
{{ config('app.name') }}
</x-mail::message>
