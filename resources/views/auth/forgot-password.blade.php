<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Set Password | Dhaka IT Institute</title>@vite(['resources/css/app.css'])</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
<main class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
    <h1 class="text-2xl font-bold text-gray-900">Set or reset password</h1>
    <p class="mt-2 text-sm text-gray-600">Approved students receive a secure, expiring link by email.</p>
    @if(session('success'))<div class="mt-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    @error('email')<div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>@enderror
    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf
        <label class="block text-sm font-semibold">Email<input type="email" name="email" value="{{ old('email') }}" required autofocus class="mt-2 w-full rounded-lg border-gray-300"></label>
        <button class="w-full rounded-lg bg-primary px-4 py-3 font-semibold text-white">Send secure link</button>
    </form>
    <a href="{{ route('login') }}" class="mt-5 block text-center text-sm text-primary hover:underline">Back to login</a>
</main>
</body></html>
