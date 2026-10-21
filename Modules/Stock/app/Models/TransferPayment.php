<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Configuration\Models\Outlet;
use Modules\Accounting\Models\PaymentMethod;
use App\Models\User;

class TransferPayment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected $appends = [
        'encrypted_id',
    ];

    /**
     * The outlet that owes the money (usually the main/sending outlet).
     */
    public function fromOutlet()
    {
        return $this->belongsTo(Outlet::class, 'from_outlet_id');
    }

    /**
     * The outlet making the payment (the receiving/branch outlet).
     */
    public function toOutlet()
    {
        return $this->belongsTo(Outlet::class, 'to_outlet_id');
    }

    /**
     * The payment method/account used.
     */
    public function account()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    /**
     * The user who recorded the payment.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the encrypted ID attribute
     */
    public function getEncryptedIdAttribute()
    {
        return encrypt($this->id);
    }
}
