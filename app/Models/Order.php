<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['orderId', 'order_type', 'binance_order', 'user_id', 'parent_order_id'];

    // Si necesitas una relación con la orden padre
    public function parentOrder()
    {
        return $this->belongsTo(Order::class, 'parent_order_id');
    }

    // Si quieres obtener las órdenes hijas
    public function childOrders()
    {
        return $this->hasMany(Order::class, 'parent_order_id');
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    // Método para encontrar una Order por su orderId
    public static function findByOrderId(string $orderId)
    {
        return self::where('orderId', $orderId)->first();
    }
}
