<?php

return [
    'methods' => [
        'bkash' => [
            'name' => 'bKash',
            'number' => env('BKASH_ADMIN_NUMBER', '01XXXXXXXXX'),
            'account_name' => env('BKASH_ACCOUNT_NAME', 'LMS Admin'),
            'instructions' => 'Open bKash, choose Send Money, enter the admin number shown here, enter the exact course fee, confirm with your PIN, then submit the sender number, bKash Transaction ID, and a clear receipt screenshot below. Access is activated only after admin verification.',
            'icon' => 'bkash-icon.png',
        ],
        'nagad' => [
            'name' => 'Nagad',
            'number' => '01XXXXXXXXX',
            'instructions' => 'Send money to the above Nagad number using "Send Money" option. After successful payment, note down the Transaction ID and upload a screenshot of the transaction.',
            'icon' => 'nagad-icon.png',
        ],
        'rocket' => [
            'name' => 'Rocket',
            'number' => '01XXXXXXXXX',
            'instructions' => 'Send money to the above Rocket number. After successful payment, note down the Transaction ID and upload a screenshot of the transaction.',
            'icon' => 'rocket-icon.png',
        ],
        'bank_transfer' => [
            'name' => 'Bank Transfer',
            'details' => [
                'bank_name' => 'Example Bank',
                'account_name' => 'Institution Name',
                'account_number' => 'XXXXXXXXXXXX',
                'branch' => 'Main Branch',
            ],
            'instructions' => 'Transfer the course fee to the above bank account. After successful transfer, note down the Transaction ID and upload a screenshot or photo of the bank receipt.',
            'icon' => 'bank-icon.png',
        ],
    ],
];
