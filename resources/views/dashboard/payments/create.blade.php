@extends('layouts.admin')

@section('title', 'Record Payment')
@section('page-title', 'Record Payment')
@section('page-description', 'Record a new payment for a student')

@section('content')
    <form class="max-w-4xl mx-auto" action="{{ route('dashboard.payments.store') }}" method="POST">
        @csrf

        {{-- Global Validation Error Display --}}
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
                <div class="flex items-center mb-2">
                    <svg class="w-5 h-5 mr-2 fill-current" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <strong class="font-semibold">Please fix the following errors:</strong>
                </div>
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Student Selection -->
        <div class="bg-white rounded-xl shadow-md border-gray-200 border mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Student Information</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <x-ui.select name="student_id" id="student_id" label="Select Student" required>
                            <option value="">-- Select a Student --</option>
                            @foreach($students as $s)
                                <option value="{{ $s->id }}" 
                                    data-balance="{{ $s->due_amount }}"
                                    data-total="{{ $s->total_amount }}"
                                    data-paid="{{ $s->paid_amount }}"
                                    {{ old('student_id', $student?->id) == $s->id ? 'selected' : '' }}>
                                    {{ $s->user->name ?? 'N/A' }} - {{ $s->registration_no ?? 'No Reg' }} 
                                    (Due: ৳{{ number_format($s->due_amount, 2) }})
                                </option>
                            @endforeach
                        </x-ui.select>
                        @error('student_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Student Balance Display -->
                    <div id="student-balance-info" class="md:col-span-2 hidden">
                        <div class="bg-gray-50 rounded-lg p-4 grid grid-cols-3 gap-4">
                            <div class="text-center">
                                <p class="text-xs text-gray-500 uppercase">Total Amount</p>
                                <p class="text-lg font-bold text-gray-900" id="display-total">৳0.00</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-gray-500 uppercase">Paid Amount</p>
                                <p class="text-lg font-bold text-green-600" id="display-paid">৳0.00</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-gray-500 uppercase">Due Amount</p>
                                <p class="text-lg font-bold text-red-600" id="display-due">৳0.00</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="bg-white rounded-xl shadow-md border-gray-200 border mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Payment Details</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Amount -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                            Payment Amount <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">৳</span>
                            <input type="number" name="amount" id="amount" step="0.01" min="0.01"
                                value="{{ old('amount') }}" required
                                class="w-full pl-8 pr-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-bd-green focus:border-transparent"
                                placeholder="0.00">
                        </div>
                        @error('amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <x-ui.date-picker name="payment_date" id="payment_date" label="Payment Date"
                            value="{{ old('payment_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" />
                    </div>

                    <!-- Payment Method -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Payment Method <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($paymentMethods as $value => $label)
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="payment_method" value="{{ $value }}"
                                        class="peer sr-only" {{ old('payment_method') == $value ? 'checked' : '' }}>
                                    <div class="p-4 border-2 rounded-lg text-center transition-all
                                        peer-checked:border-bd-green peer-checked:bg-green-50
                                        hover:border-gray-400">
                                        @if($value === 'cash')
                                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                        @elseif($value === 'bkash')
                                            <div class="w-8 h-8 mx-auto mb-2 bg-pink-500 rounded-full flex items-center justify-center text-white font-bold text-xs">bK</div>
                                        @elseif($value === 'nagad')
                                            <div class="w-8 h-8 mx-auto mb-2 bg-orange-500 rounded-full flex items-center justify-center text-white font-bold text-xs">N</div>
                                        @else
                                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                            </svg>
                                        @endif
                                        <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('payment_method')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Money Instructions (shown conditionally) -->
        <div id="mobile-money-info" class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-6 hidden">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h4 class="text-sm font-semibold text-blue-800" id="mobile-money-title">Payment Instructions</h4>
                    <p class="mt-1 text-sm text-blue-700">
                        <span class="font-medium">Send money to:</span>
                        <span id="mobile-money-phone" class="font-bold">Configured institute number</span>
                    </p>
                    <p class="mt-1 text-sm text-blue-600" id="mobile-money-instructions">
                        Send money to the above number and provide the Transaction ID below.
                    </p>
                </div>
            </div>
        </div>

        <!-- Transaction Details (for non-cash payments) -->
        <div id="transaction-details" class="bg-white rounded-xl shadow-md border-gray-200 border mb-6 hidden">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Transaction Details</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Transaction ID -->
                    <div>
                        <label for="transaction_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Transaction ID <span class="text-red-500" id="txn-required">*</span>
                        </label>
                        <input type="text" name="transaction_id" id="transaction_id"
                            value="{{ old('transaction_id') }}"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-bd-green focus:border-transparent"
                            placeholder="Enter transaction ID">
                        @error('transaction_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Mobile Number (for bKash/Nagad) -->
                    <div id="mobile-number-field">
                        <label for="mobile_number" class="block text-sm font-medium text-gray-700 mb-1">
                            Sender's Mobile Number <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" name="mobile_number" id="mobile_number"
                            value="{{ old('mobile_number') }}"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-bd-green focus:border-transparent"
                            placeholder="017XXXXXXXX">
                        @error('mobile_number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Selection (Optional) -->
        <div class="bg-white rounded-xl shadow-md border-gray-200 border mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Invoice Reference (Optional)</h3>
            </div>
            <div class="p-6">
                <div>
                    <x-ui.select name="invoice_id" id="invoice_id" label="Link to Invoice">
                        <option value="">-- No Invoice --</option>
                        @if(isset($pendingInvoices) && count($pendingInvoices) > 0)
                            @foreach($pendingInvoices as $invoice)
                                <option value="{{ $invoice->id }}" {{ old('invoice_id') == $invoice->id ? 'selected' : '' }}>
                                    {{ $invoice->invoice_number }} - ৳{{ number_format($invoice->amount, 2) }} 
                                    (Due: {{ $invoice->due_date->format('M d, Y') }})
                                </option>
                            @endforeach
                        @endif
                    </x-ui.select>
                    <p class="mt-1 text-xs text-gray-500">Select an invoice to link this payment to.</p>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="bg-white rounded-xl shadow-md border-gray-200 border mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Additional Notes</h3>
            </div>
            <div class="p-6">
                <textarea name="notes" id="notes" rows="3"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-bd-green focus:border-transparent"
                    placeholder="Any additional notes about this payment...">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 mb-10">
            <a href="{{ route('dashboard.payments.index') }}"
                class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                Cancel
            </a>
            <button type="submit"
                class="px-6 py-2.5 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors font-medium">
                Record Payment
            </button>
        </div>
    </form>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const studentSelect = document.getElementById('student_id');
            const balanceInfo = document.getElementById('student-balance-info');
            const displayTotal = document.getElementById('display-total');
            const displayPaid = document.getElementById('display-paid');
            const displayDue = document.getElementById('display-due');
            
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            const mobileMoneyInfo = document.getElementById('mobile-money-info');
            const mobileMoneyTitle = document.getElementById('mobile-money-title');
            const mobileMoneyPhone = document.getElementById('mobile-money-phone');
            const mobileMoneyInstructions = document.getElementById('mobile-money-instructions');
            const transactionDetails = document.getElementById('transaction-details');
            const mobileNumberField = document.getElementById('mobile-number-field');

            // Mobile money configuration
            const mobileMoneyConfig = @json($mobileMoneyConfig);

            // Update student balance display
            studentSelect.addEventListener('change', function() {
                const selected = this.options[this.selectedIndex];
                if (this.value) {
                    const total = parseFloat(selected.dataset.total) || 0;
                    const paid = parseFloat(selected.dataset.paid) || 0;
                    const due = parseFloat(selected.dataset.balance) || 0;
                    
                    displayTotal.textContent = '৳' + total.toLocaleString('en-BD', {minimumFractionDigits: 2});
                    displayPaid.textContent = '৳' + paid.toLocaleString('en-BD', {minimumFractionDigits: 2});
                    displayDue.textContent = '৳' + due.toLocaleString('en-BD', {minimumFractionDigits: 2});
                    
                    balanceInfo.classList.remove('hidden');
                } else {
                    balanceInfo.classList.add('hidden');
                }
            });

            // Handle payment method change
            paymentMethods.forEach(radio => {
                radio.addEventListener('change', function() {
                    const method = this.value;
                    
                    if (method === 'cash') {
                        mobileMoneyInfo.classList.add('hidden');
                        transactionDetails.classList.add('hidden');
                    } else if (method === 'bkash' || method === 'nagad') {
                        // Show mobile money instructions
                        const config = mobileMoneyConfig[method] || {};
                        mobileMoneyTitle.textContent = method === 'bkash' ? 'bKash Payment Instructions' : 'Nagad Payment Instructions';
                        mobileMoneyPhone.textContent = config.phone || 'Not configured — ask the office for the number.';
                        mobileMoneyInstructions.textContent = config.instructions || '';
                        mobileMoneyInfo.classList.remove('hidden');
                        
                        // Show transaction details with mobile number
                        transactionDetails.classList.remove('hidden');
                        mobileNumberField.classList.remove('hidden');
                    } else if (method === 'bank_transfer') {
                        mobileMoneyInfo.classList.add('hidden');
                        transactionDetails.classList.remove('hidden');
                        mobileNumberField.classList.add('hidden');
                    }
                });
            });

            // Trigger change event if a method is already selected (for validation errors)
            const checkedMethod = document.querySelector('input[name="payment_method"]:checked');
            if (checkedMethod) {
                checkedMethod.dispatchEvent(new Event('change'));
            }

            // Trigger student change if already selected
            if (studentSelect.value) {
                studentSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
    @endpush
@endsection
