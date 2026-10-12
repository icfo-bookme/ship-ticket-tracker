<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShipTicketSale extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ship_ticket_sales';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_name',
        'customer_mobile',
        'whatsapp',
        'whatsapp_username',
        'sales_source',
        'sales_source_id',
        'ship_id',
        'journey_date',
        'return_date',
        'company_id',
        'issued_date',
        'sold_by',
        'nid',
        'email',
        'status',
        'date_of_birth',
        'other_fee',
        'discount_amount',
        'total_payable',
        'address',
        'collect_from_office',
        'remark1',
        'remark2',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [

        'discount_amount' => 'decimal:2',
        'collect_from_office' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['due_amount'];

    public function getTicketCountAttribute(): int
    {
        if ($this->relationLoaded('categories')) {
            return (int) $this->categories->sum('quantity');
        }

        return (int) $this->categories()->sum('quantity');
    }

    public function getTicketValueAttribute(): float
    {
        if ($this->relationLoaded('categories')) {
            return round((float) $this->categories->sum(
                fn (Category $category): float => (int) $category->quantity * (float) $category->unit_price
            ), 2);
        }

        return round((float) $this->categories()
            ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) AS aggregate')
            ->value('aggregate'), 2);
    }

    public function getDueAmountAttribute(): string
    {
        $dueAmount = app(\App\Services\Sales\SaleFinancialService::class)
            ->currentSummary($this)['due_amount'];

        return number_format($dueAmount, 2, '.', '');
    }

    public static function dueAmountSql(): string
    {
        $totalPayable = 'COALESCE(ship_ticket_sales.total_payable, 0)';
        $settledPayments = '(SELECT COALESCE(SUM(CASE WHEN payments.is_bftn = 0 OR payments.settlement_status = "received" THEN payments.received_amount ELSE 0 END), 0) FROM payments WHERE payments.sales_id = ship_ticket_sales.id)';
        $pendingBftnPayments = '(SELECT COALESCE(SUM(received_amount), 0) FROM payments WHERE payments.sales_id = ship_ticket_sales.id AND payments.is_bftn = 1 AND payments.settlement_status = "pending")';
        $refundDueAdjustments = '(SELECT COALESCE(SUM(due_adjusted_amount), 0) FROM refunds WHERE refunds.sales_id = ship_ticket_sales.id AND (refunds.status = "completed" OR refunds.customer_refunded_at IS NOT NULL))';
        $dueAmount = "{$totalPayable} - {$settledPayments} - {$pendingBftnPayments} - {$refundDueAdjustments}";

        return "CASE WHEN {$dueAmount} > 0 THEN {$dueAmount} ELSE 0 END";
    }

    /**
     * Scope a query to only include pending dues.
     */
    public function scopeWithDue(Builder $query): Builder
    {
        return $query->whereRaw('('.self::dueAmountSql().') > 0');
    }

    /**
     * Scope a query to only include paid tickets.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->whereRaw('('.self::dueAmountSql().') = 0');
    }

    /**
     * Check if the ticket sale has due amount.
     */
    public function hasDue(): bool
    {
        return $this->due_amount > 0;
    }

    /**
     * Get the total paid amount.
     */
    public function getTotalPaidAttribute(): float
    {
        return app(\App\Services\Sales\SaleFinancialService::class)
            ->currentSummary($this)['received_amount'];
    }

    public function ships()
    {
        return $this->hasOne(Ship::class, 'id', 'ship_id');
    }

    public function companies()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    /**
     * Get the user who sold the ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function refund()
    {
        return $this->hasOne(Refund::class, 'sales_id', 'id');
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class, 'sales_id', 'id');
    }

    public function coPassengers()
    {
        return $this->hasMany(CoPassenger::class, 'ship_ticket_sale_id');
    }

    public function verifyby()
    {
        return $this->hasMany(VerifyTracker::class, 'ticket_id', 'id');
    }

    public function ticketIssueViews(): HasMany
    {
        return $this->hasMany(TicketIssueView::class, 'sales_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'sales_id');
    }

    public function salesSource(): BelongsTo
    {
        return $this->belongsTo(SalesSource::class, 'sales_source_id');
    }

    public function categories()
    {
        return $this->hasMany(Category::class, 'ticket_id');
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class, 'ticket_id', 'id');
    }

    public function printStatus()
    {
        return $this->hasOne(PrintStatus::class, 'sales_id', 'id');
    }

    // In ShipTicketSale model
    public function printedTickets()
    {
        return $this->hasMany(PrintedTicket::class, 'sales_id', 'id');
    }

    public function groupedTickets()
    {
        return $this->hasMany(PrintedTicket::class, 'group_by_id', 'id');
    }
}
