<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;   // nếu có model Order
use App\Models\OrderItem;

class CheckoutController extends Controller
{
    // Hiển thị trang thanh toán
    public function show()
    {
        $item = session('checkout_item');
        if (! $item) {
            return redirect()->route('home'); 
        }
        return view('checkout.index', compact('item'));
    }

    // Xử lý khi người dùng submit form
    public function process(Request $request)
    {
        $data = $request->validate([
            'customer_name'    => 'required|string|max:255',
            'customer_email'   => 'required|email',
            'customer_address' => 'required|string',
            'customer_phone'   => 'required|string',
            'payment_method'   => 'required|in:cod,card',
        ]);

        $item = session('checkout_item');
        if (! $item) {
            return redirect()->route('home');
        }

        // Ví dụ lưu đơn hàng (Order) và chi tiết (OrderItem)
        $order = Order::create([
            'name'     => $data['customer_name'],
            'email'    => $data['customer_email'],
            'address'  => $data['customer_address'],
            'phone'    => $data['customer_phone'],
            'total'    => $item['price'] * $item['quantity'],
            'method'   => $data['payment_method'],
            'status'   => 'pending',
        ]);

        OrderItem::create([
            'order_id'   => $order->id,
            'product_id' => $item['id'],
            'quantity'   => $item['quantity'],
            'price'      => $item['price'],
        ]);

        // Sau khi lưu xong, xóa session và chuyển về trang cảm ơn
        session()->forget('checkout_item');

        return view('checkout.thankyou', ['order' => $order]);
    }
}
