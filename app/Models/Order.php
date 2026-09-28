<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'order_id';

    protected $fillable = [
        'order_number',
        'customer_name',
        'service_type',
        'status',
        'total_amount',
        'user_id',
        'order_date',
        'processed_by',
    ];
    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by', 'id');
    }

    /**
     * Unique sequential ticket number, e.g. KC-0001, generated under a lock
     * so concurrent checkouts can never collide.
     */
    public static function generateOrderNumber(): string
    {
        return DB::transaction(function () {
            $max = self::query()->max('order_id') ?? 0;

            return 'KC-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
        });
    }
}
