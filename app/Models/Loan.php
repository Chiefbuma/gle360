<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Loan extends Model
{
    use HasFactory;

    // Primary key and table name
    protected $primaryKey = 'loan_id';
    protected $table = 'loan';
    public $timestamps = true;

    // Status constants
    const STATUS_REQUEST = 0;
    const STATUS_COMPLETE = 1;
    const STATUS_OVERDUE = 3;

    protected $fillable = [
        'ltype_id',
        'borrower_id',
        'amount',
        'duration',
        'total_loan',
        'daily_amount',
        'lplan_id',
        'status',
        'date_released'
    ];

    protected $casts = [
        'date_released' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_REQUEST, // Default to 'Request'
    ];

    protected $appends = [
        'total_payable_amount',
        'penalty_amount',
        'due_date',
        'paid_amount',
        'remaining_amount',
        'status_label',
        'status_badge',
        'payment_progress',
    ];

    // Relationships
    public function borrower()
    {
        return $this->belongsTo(Borrower::class, 'borrower_id');
    }

    public function loanType()
    {
        return $this->belongsTo(LoanType::class, 'ltype_id');
    }

    public function loanPlan()
    {
        return $this->belongsTo(LoanPlan::class, 'lplan_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'loan_id');
    }

    // Payment calculations
    public function totalPaid()
    {
        return $this->payments()->sum('payment_amount');
    }

    public function remainingBalance()
    {
        return max(0, $this->total_loan - $this->totalPaid());
    }

    // Status checks
    public function isFullyPaid()
    {
        return $this->remainingBalance() <= 0;
    }

    public function isOverdue()
    {
        if ($this->isFullyPaid()) {
            return false;
        }

        $dueDate = $this->due_date ? Carbon::parse($this->due_date) : null;
        return $dueDate && Carbon::today()->gt($dueDate);
    }

    // Status management
    public function updateStatus()
    {
        $newStatus = $this->status;

        if ($this->isFullyPaid()) {
            $newStatus = self::STATUS_COMPLETE;
        } elseif ($this->isOverdue()) {
            $newStatus = self::STATUS_OVERDUE;
        }

        if ($this->status !== $newStatus) {
            $this->status = $newStatus;
            $this->save();
        }

        return $this;
    }

    // Accessors
    public function getTotalPayableAmountAttribute()
    {
        return $this->total_loan ?? ($this->amount + ($this->amount * ($this->loanPlan->lplan_interest ?? 0) / 100));
    }

    public function getPenaltyAmountAttribute()
    {
        return $this->loanPlan->lplan_penalty ?? 0;
    }

    public function getDueDateAttribute()
    {
        if ($this->date_released && $this->duration) {
            return Carbon::parse($this->date_released)->addDays(round($this->duration, 0))->toDateString();
        }
        return null;
    }

    public function getPaidAmountAttribute()
    {
        return $this->totalPaid();
    }

    public function getRemainingAmountAttribute()
    {
        return $this->remainingBalance();
    }

    public function getPaymentProgressAttribute()
    {
        if ($this->total_loan <= 0) return 0;
        return ($this->totalPaid() / $this->total_loan) * 100;
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            self::STATUS_REQUEST => 'Pending',
            self::STATUS_COMPLETE => 'Complete',
            self::STATUS_OVERDUE => 'Overdue',
            default => 'Unknown',
        };
    }

    public function getStatusBadgeAttribute()
    {
        return match ($this->status) {
            self::STATUS_COMPLETE => [
                'label' => 'Complete',
                'color' => 'success',
                'icon' => 'heroicon-o-check-circle',
            ],
            self::STATUS_OVERDUE => [
                'label' => 'Overdue',
                'color' => 'danger',
                'icon' => 'heroicon-o-exclamation-circle',
            ],
            default => [
                'label' => 'Pending',
                'color' => 'warning',
                'icon' => 'heroicon-o-clock',
            ],
        };
    }

    // Helper methods
    public function hasPayments(): bool
    {
        return $this->payments()->exists();
    }

    public function daysOverdue(): ?int
    {
        if (!$this->isOverdue()) {
            return null;
        }

        return Carbon::today()->diffInDays(Carbon::parse($this->due_date));
    }
}