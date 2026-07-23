<?php

return [
    'title' => 'Tölegler',
    'subtitle' => 'Ussatlara tölegler we hasaplama taryhy',
    'currency' => 'manat',

    'stats' => [
        'total_paid' => 'Jemi tölendi',
        'pending_payouts' => 'Töleg garaşýar',
        'masters_with_balance' => 'Balansy bolan ussatlar',
    ],

    'pending_title' => 'Balansy bolan ussatlar',
    'history_title' => 'Töleg taryhy',
    'no_pending' => 'Tölege garaşýan balansly ussa ýok',
    'no_history' => 'Heniz töleg geçirilmedi',
    'payout_btn' => 'Töle',

    'fields' => [
        'master' => 'Usta',
        'city' => 'Şäher',
        'amount' => 'Möçber',
        'balance' => 'Balans',
        'model' => 'Töleg modeli',
        'paid_by' => 'Tölän',
        'note' => 'Bellik',
        'date' => 'Senesi',
        'action' => 'Hereket',
    ],

    'modal' => [
        'title' => 'Ussa töleg',
        'message' => 'Usta :name. Tölege elýeterli: :amount.',
        'amount' => 'Töleg möçberi',
        'full' => 'Ähli balans',
        'note' => 'Bellik (hökmany däl)',
        'note_placeholder' => 'Meselem: nagt töleg',
    ],

    'notifications' => [
        'paid' => 'Töleg geçirildi',
    ],

    'errors' => [
        'nothing_to_pay' => 'Ussanyň tölege balansy ýok',
        'exceeds_balance' => 'Möçber ussanyň elýeterli balansyndan köp',
    ],
];
