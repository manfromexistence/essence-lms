<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Create Password | Dhaka IT Institute</title>@vite(['resources/css/app.css'])</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
<main class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
    <h1 class="text-2xl font-bold">Create your password</h1>
    @if($errors->any())<div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label class="block text-sm font-semibold">Email<input type="email" name="email" value="{{ old('email', $email) }}" required class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder:text-gray-400"></label>
        <label class="block text-sm font-semibold">New password<input type="password" name="password" required autocomplete="new-password" class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder:text-gray-400"></label>
        <label class="block text-sm font-semibold">Confirm password<input type="password" name="password_confirmation" required autocomplete="new-password" class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder:text-gray-400"></label>
        <p class="text-xs text-gray-500">Use at least 12 characters with upper/lowercase letters, a number, and a symbol.</p>
        <button class="w-full rounded-lg bg-primary px-4 py-3 font-semibold text-white">Save password</button>
    </form>
</main>
</body></html>
