@extends('layouts.admin')

@section('title', 'Course Payment - ' . $course->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Course Payment</h1>
            <p class="text-gray-600 mt-2">Complete your payment to enroll in the course</p>
        </div>

        <!-- Course Information Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Course Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Course Name</p>
                    <p class="font-semibold text-gray-800">{{ $course->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Course Fee</p>
                    <p class="font-semibold text-gray-800 text-2xl text-green-600">৳{{ number_format($course->price, 2) }}</p>
                </div>
                @if($course->description)
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-600">Description</p>
                    <p class="text-gray-700">{{ $course->description }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Payment Form -->
        <form action="{{ route('student.payment.submit') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-md p-6">
            @csrf
            <input type="hidden" name="course_id" value="{{ $course->id }}">

            <h2 class="text-xl font-semibold text-gray-800 mb-6">Payment Information</h2>

            <!-- Payment Method Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Select Payment Method *</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($paymentMethods as $key => $method)
                    <div class="border-2 border-gray-200 rounded-lg p-4 hover:border-blue-500 transition cursor-pointer payment-method-card" data-method="{{ $key }}">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="payment_method" value="{{ $key }}" class="mr-3" required>
                            <div>
                                <p class="font-semibold text-gray-800">{{ $method['name'] }}</p>
                                @if(isset($method['number']))
                                <p class="text-sm text-gray-600">{{ $method['number'] }}</p>
                                @endif
                            </div>
                        </label>
                    </div>
                    @endforeach
                </div>
                @error('payment_method')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Payment Instructions (Dynamic based on selection) -->
            <div id="payment-instructions" class="mb-6 hidden">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                    <h3 class="font-semibold text-blue-800 mb-2">Payment Instructions</h3>
                    <p id="instructions-text" class="text-blue-700 text-sm"></p>
                    <div id="bank-details" class="mt-3 hidden">
                        <p class="text-sm text-blue-700"><strong>Bank:</strong> <span id="bank-name"></span></p>
                        <p class="text-sm text-blue-700"><strong>Account Name:</strong> <span id="account-name"></span></p>
                        <p class="text-sm text-blue-700"><strong>Account Number:</strong> <span id="account-number"></span></p>
                        <p class="text-sm text-blue-700"><strong>Branch:</strong> <span id="branch"></span></p>
                    </div>
                </div>
            </div>

            <!-- Amount -->
            <div class="mb-6">
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount Paid *</label>
                <input type="text" value="৳{{ number_format($course->price, 2) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" readonly>
                @error('amount')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="sender_number" class="block text-sm font-medium text-gray-700 mb-2">Sender bKash Number *</label>
                <input type="tel" id="sender_number" name="sender_number" value="{{ old('sender_number') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="01XXXXXXXXX">
                @error('sender_number')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Transaction ID -->
            <div class="mb-6">
                <label for="transaction_id" class="block text-sm font-medium text-gray-700 mb-2">Transaction ID *</label>
                <input type="text" id="transaction_id" name="transaction_id" value="{{ old('transaction_id') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                    placeholder="Enter transaction ID from payment receipt" required>
                @error('transaction_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Screenshot Upload -->
            <div class="mb-6">
                <label for="screenshot" class="block text-sm font-medium text-gray-700 mb-2">Payment Screenshot *</label>
                <input type="file" id="screenshot" name="screenshot" accept=".jpg,.jpeg,.png,.pdf" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                <p class="text-sm text-gray-500 mt-1">Upload a clear screenshot or photo of your payment receipt (JPG, PNG, or PDF, max 5MB)</p>
                @error('screenshot')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Notes (Optional) -->
            <div class="mb-6">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Additional Notes (Optional)</label>
                <textarea id="notes" name="notes" rows="3" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                    placeholder="Any additional information about your payment"></textarea>
                @error('notes')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="flex justify-between items-center">
                <a href="{{ route('student.courses') }}" class="text-gray-600 hover:text-gray-800">
                    ← Back to Courses
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-lg transition">
                    Submit Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Payment method instructions
const paymentMethods = @json($paymentMethods);

document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const method = this.value;
        const methodData = paymentMethods[method];
        const instructionsDiv = document.getElementById('payment-instructions');
        const instructionsText = document.getElementById('instructions-text');
        const bankDetails = document.getElementById('bank-details');

        if (methodData) {
            instructionsDiv.classList.remove('hidden');
            instructionsText.textContent = methodData.instructions;

            // Show bank details if bank transfer
            if (method === 'bank_transfer' && methodData.details) {
                bankDetails.classList.remove('hidden');
                document.getElementById('bank-name').textContent = methodData.details.bank_name;
                document.getElementById('account-name').textContent = methodData.details.account_name;
                document.getElementById('account-number').textContent = methodData.details.account_number;
                document.getElementById('branch').textContent = methodData.details.branch;
            } else {
                bankDetails.classList.add('hidden');
            }
        }
    });
});

// Highlight selected payment method card
document.querySelectorAll('.payment-method-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.payment-method-card').forEach(c => {
            c.classList.remove('border-blue-500', 'bg-blue-50');
        });
        this.classList.add('border-blue-500', 'bg-blue-50');
        this.querySelector('input[type="radio"]').checked = true;
        this.querySelector('input[type="radio"]').dispatchEvent(new Event('change'));
    });
});
</script>
@endsection
