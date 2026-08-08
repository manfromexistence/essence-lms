<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Change Password | Dhaka IT Institute</title>@vite(['resources/css/app.css'])</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
<main class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
    <h1 class="text-2xl font-bold">Secure your account</h1>
    <p class="mt-2 text-sm text-gray-600">Replace the temporary password before continuing.</p>
    @if($errors->any())<div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.change.update') }}" class="mt-6 space-y-4">
        @csrf @method('PUT')
        <label class="block text-sm font-semibold">Current password<x-ui.password-input name="current_password" required autocomplete="current-password" placeholder="Enter your current password" /></label>
        <label class="block text-sm font-semibold">New password<x-ui.password-input name="password" required autocomplete="new-password" placeholder="Enter a strong password" /></label>
        <label class="block text-sm font-semibold">Confirm password<x-ui.password-input name="password_confirmation" required autocomplete="new-password" placeholder="Re-enter password" /></label>
        <button class="w-full rounded-lg bg-primary px-4 py-3 font-semibold text-white">Change password</button>
    </form>
</main>
</body></html>
