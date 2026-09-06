<?php

return [
    'methods' => [
        'bkash' => [
            'name' => 'bKash',
            'number' => env('BKASH_ADMIN_NUMBER'),
            'account_name' => env('BKASH_ACCOUNT_NAME', 'Dhaka IT Institute'),
            'instructions' => 'Open bKash, choose Send Money, enter the admin number shown here, enter the exact course fee, confirm with your PIN, then submit the sender number, bKash Transaction ID, and a clear receipt screenshot below. Access is activated only after admin verification.',
            'icon' => 'bkash-icon.png',
        ],
        'nagad' => [
            'name' => 'Nagad',
            'number' => env('NAGAD_ADMIN_NUMBER'),
            'instructions' => 'Send money to the Nagad number configured by the institute using "Send Money". After successful payment, note down the Transaction ID and upload a screenshot of the transaction.',
            'icon' => 'nagad-icon.png',
        ],
        'rocket' => [
            'name' => 'Rocket',
            'number' => env('ROCKET_ADMIN_NUMBER'),
            'instructions' => 'Send money to the Rocket number configured by the institute. After successful payment, note down the Transaction ID and upload a screenshot of the transaction.',
            'icon' => 'rocket-icon.png',
        ],
        'bank_transfer' => [
            'name' => 'Bank Transfer',
            'details' => [
                'bank_name' => env('BANK_NAME'),
                'account_name' => env('BANK_ACCOUNT_NAME'),
                'account_number' => env('BANK_ACCOUNT_NUMBER'),
                'branch' => env('BANK_BRANCH'),
            ],
            'instructions' => 'Transfer the course fee to the institute bank account configured in Settings. After successful transfer, note down the Transaction ID and upload a screenshot or photo of the bank receipt.',
            'icon' => 'bank-icon.png',
        ],
    ],
];
