<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Order\OrderServiceInterface;
use App\Services\OrderDetail\OrderDetailServiceInterface;
use App\Utillities\Constant;
use App\Utillities\VNPay;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class   CheckOutController extends Controller
{
    private $orderService;
    private $orderDetailService;

    public function __construct(OrderServiceInterface $orderService,
                                OrderDetailServiceInterface $orderDetailService) {
        $this->orderService = $orderService;
        $this->orderDetailService = $orderDetailService;
    }

    public function index() {
        $carts = Cart::content();
        $total = Cart::total();
        $subtotal = Cart::subtotal();

        return view('front.checkout.index', compact('carts', 'total', 'subtotal'));
    }

    public function addOrder(Request $request) {
        //01. Thêm đơn hàng
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'country' => 'required',
            'street_address' => 'required',
            'town_city' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
        ]);
        $data = $request->all();
        $data['status'] = Constant::order_status_ReceiveOrders;
        $order = $this->orderService->create($data);


        //02. Thêm chi tiết đơn hàng
        $carts = Cart::content();

        foreach ($carts as $cart) {
            $data = [
                'order_id' => $order->id,
                'product_id' => $cart->id,
                'qty' => $cart->qty,
                'amount' => $cart->price,
                'total' => $cart->qty * $cart->price,
            ];

            $this->orderDetailService->create($data);
        }

        if($request->payment_type == 'pay_later') {
            //Gửi email
            $total = Cart::total();
            $subtotal = Cart::subtotal();
            $this->sendEmail($order, $total, $subtotal); //GỌi hàm gửi email đã định nghĩa

            //03. Xóa giỏ hàng
            Cart::destroy();

            //04. Trả về kết quả thông báo
            return redirect('checkout/result')
                ->with('notification', 'Success! You will pay on delivery.');
        }

        if($request->payment_type == 'online_payment') {
            //01. Lấy URL thanh toán VNPay
            $data_url = VNPay::vnpay_create_payment([
                'vnp_TxnRef' => $order->id, // ID của đơn hàng
                'vnp_OrderInfo' => "Mo ta", // Mô tả đơn hàng (điền tùy ý phù hợp)
                'vnp_Amount' => Cart::total(0, '', '') * 23070, // Tổng giá của đơn hàng
            ]);

            //02. chuyển hướng tới ỦL lấy được
            return redirect()->to($data_url);
        }


    }

    public function vnPayCheck(Request $request) {
        //01. Lấy data từ URL (do VNPay gửi về qua $vnp_Returnurl);
        $vnp_ResponseCode = $request->get('vnp_ResponseCode'); // Mã phản hồi kết quả thanh toán. 00 = thành công
        $vnp_TxnRef = $request->get('vnp_TxnRef'); //order_id
        $vnp_Amount = $request->get('vnp_Amount'); // số tiền thanh toán

        //02. Kiểm tra data, xem kết quả giao dịch trả về từ VNPay hợp lệ không
        if($vnp_ResponseCode != null) {
            // Nếu thành công
            if($vnp_ResponseCode == 0 ) {
                //cập nhập trạng thái Order:
                $this->orderService->update(['status' => Constant::order_status_Paid], $vnp_TxnRef);

                //Gửi email
                $order = $this->orderService->find($vnp_TxnRef); //$vnp_TxnRef chính là order_id
                $total = Cart::total();
                $subtotal = Cart::subtotal();
                $this->sendEmail($order, $total, $subtotal); //GỌi hàm gửi email đã định nghĩa


                // xóa giỏ hàng
                Cart::destroy();

                //thông báo kết quả.
                return redirect('checkout/result')
                    ->with('notification', 'Success! You will pay on delivery, Please check your email.');


            } else {
            // nếu không thành công
            // Xóa đơn hàng đã thêm bào dataBase
            $this->orderService->delete($vnp_TxnRef);

            //thông báo lỗi
            return redirect('checkout/result')
                ->with('notification', 'Error! Payment failed or canceled.');

            }
        }
    }

    public function result() {
        $notification = session('notification');
        return view('front.checkout.result', compact('notification'));
    }

    private function sendEmail($order, $total, $subtotal) {
        $email_to = $order->email;

        Mail::send('front.checkout.email',
            compact('order', 'total', 'subtotal'),
            function ($message) use ($email_to) {
                $message->from('ngtin590@gmail.com', 'Shop');
                $message->to($email_to, $email_to);
                $message->subject('Order Notification');
            }
        );
    }
}
