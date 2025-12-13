<?php

namespace App\Infrastructure\Payment\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PaymentTransaction extends Model
{
    use HasUuids;

    protected $table = 'payment_transactions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'provider',
        'order_id',
        'sinh_vien_id',
        'hoc_ky_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'pay_url',
        'result_code',
        'message',
        'callback_raw',
        'signature_valid',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'callback_raw' => 'array',
        'signature_valid' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function sinhVien()
    {
        return $this->belongsTo(\App\Infrastructure\SinhVien\Persistence\Models\SinhVien::class, 'sinh_vien_id');
    }

    public function hocKy()
    {
        return $this->belongsTo(\App\Infrastructure\Common\Persistence\Models\HocKy::class, 'hoc_ky_id');
    }
}
