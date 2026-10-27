<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\CoPassenger;
use App\Models\Payment;
use App\Models\PrintedTicket;
use App\Models\Refund;
use App\Models\RefundCustomerPayment;
use App\Models\RefundPartnerPayment;
use App\Models\RefundTicket;
use App\Models\SalesSource;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;

return [
    'timezone' => 'Asia/Dhaka',

    'events' => ['created', 'updated', 'deleted', 'restored', 'status_changed'],

    'fields' => [
        ShipTicketSale::class => [
            'customer_name' => 'string', 'customer_mobile' => 'string',
            'whatsapp' => 'string', 'whatsapp_username' => 'string',
            'nid' => 'string', 'email' => 'string', 'address' => 'string',
            'sales_source' => 'string',
            'sales_source_id' => SalesSource::class, 'ship_id' => Ship::class,
            'company_id' => Company::class, 'journey_date' => 'date',
            'return_date' => 'date', 'issued_date' => 'date', 'date_of_birth' => 'date',
            'other_fee' => 'decimal', 'discount_amount' => 'decimal',
            'total_payable' => 'decimal', 'collect_from_office' => 'boolean',
            'status' => 'string', 'remark1' => 'string', 'remark2' => 'string',
            'ticket_categories' => 'collection:'.Category::class,
            'co_passengers' => 'collection:'.CoPassenger::class,
            'payments' => 'collection:'.Payment::class,
            'shipment_id' => 'string',
            'printed_tickets' => 'collection:'.PrintedTicket::class,
        ],
        PrintedTicket::class => ['filename' => 'string', 'group_by_id' => 'integer'],
        CoPassenger::class => [
            'name' => 'string', 'nid' => 'string',
            'co_passernger_number' => 'string', 'date_of_birth' => 'date',
        ],
        Category::class => [
            'package_id' => ShipPackage::class, 'quantity' => 'integer',
            'type' => 'string', 'unit_price' => 'decimal',
        ],
        Payment::class => [
            'sales_id' => 'integer', 'payment_method' => 'string',
            'is_bftn' => 'boolean', 'settlement_status' => 'string',
            'received_amount' => 'decimal', 'remark' => 'string',
            'paid_date' => 'date', 'transaction_id' => 'string',
            'payment_datetime' => 'datetime', 'bftn_issue_datetime' => 'datetime',
            'received_at' => 'datetime',
        ],
        Refund::class => [
            'sales_id' => 'integer', 'status' => 'string', 'refund_type' => 'string',
            'reason' => 'string', 'refunded_number_of_tickets' => 'integer',
            'refunded_amount' => 'decimal', 'gross_refund_amount' => 'decimal',
            'refund_discount_amount' => 'decimal', 'other_fee_deduction' => 'decimal',
            'customer_charge_percent' => 'decimal', 'customer_charge_amount' => 'decimal',
            'partner_share_percent' => 'decimal', 'partner_share_amount' => 'decimal',
            'customer_refund_amount' => 'decimal', 'due_adjusted_amount' => 'decimal',
            'company_retained_amount' => 'decimal', 'requested_at' => 'datetime',
            'partner_received_at' => 'datetime', 'customer_refunded_at' => 'datetime',
            'remark' => 'string',
            'refund_payment_details' => 'string',
            'tickets' => 'collection:'.RefundTicket::class,
            'customer_payments' => 'collection:'.RefundCustomerPayment::class,
            'partner_payments' => 'collection:'.RefundPartnerPayment::class,
        ],
        RefundTicket::class => [
            'category_id' => 'integer', 'category_name' => 'string', 'category_type' => 'string',
            'purchased_quantity' => 'integer', 'refunded_quantity' => 'integer',
            'unit_amount' => 'decimal', 'gross_amount' => 'decimal', 'remark' => 'string',
        ],
        RefundCustomerPayment::class => [
            'amount' => 'decimal', 'payment_method' => 'string', 'transaction_id' => 'string',
            'paid_at' => 'datetime', 'status' => 'string', 'remark' => 'string',
        ],
        RefundPartnerPayment::class => [
            'requested_amount' => 'decimal', 'received_amount' => 'decimal',
            'payment_method' => 'string', 'transaction_id' => 'string',
            'requested_at' => 'datetime', 'received_at' => 'datetime',
            'status' => 'string', 'remark' => 'string',
        ],
    ],

    'collections' => [
        Refund::class => [
            'tickets' => 'tickets', 'customer_payments' => 'customerPayments', 'partner_payments' => 'partnerPayments',
        ],
        ShipTicketSale::class => [
            'ticket_categories' => 'categories',
            'co_passengers' => 'coPassengers',
            'payments' => 'payments',
            'printed_tickets' => 'printedTickets',
        ],
    ],

    'references' => [
        Category::class => ['package_id' => 'package'],
    ],
];
