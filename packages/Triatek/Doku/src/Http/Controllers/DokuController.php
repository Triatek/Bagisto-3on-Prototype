<?php

namespace Triatek\Doku\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\InvoiceRepository;

class DokuController extends Controller
{
    protected $orderRepository;
    protected $invoiceRepository;

    public function __construct(
        OrderRepository $orderRepository,
        InvoiceRepository $invoiceRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->invoiceRepository = $invoiceRepository;
    }

    public function handleNotification(Request $request)
    {
        $notificationData = $request->all();
        
        Log::info("Doku Notification Received: " . json_encode($notificationData));

        $secretKey = core()->getConfigData('sales.payment_methods.doku.secret_key');
        $dokuSignature = $request->header('Signature');
        $dokuClientId = $request->header('Client-Id');
        $dokuRequestId = $request->header('Request-Id');
        $dokuTimestamp = $request->header('Request-Timestamp');
        $targetPath = $request->getRequestUri(); 

        $jsonPayload = $request->getContent();
        $digest = base64_encode(hash('sha256', $jsonPayload, true));

        $components = [
            "Client-Id:" . $dokuClientId,
            "Request-Id:" . $dokuRequestId,
            "Request-Timestamp:" . $dokuTimestamp,
            "Request-Target:" . $targetPath,
            "Digest:" . $digest
        ];

        $stringToSign = implode("\n", $components);
        $signature = "HMACSHA256=" . base64_encode(hash_hmac('sha256', $stringToSign, $secretKey, true));

        if (!hash_equals($signature, (string) $dokuSignature)) {
            Log::error("Doku Notification Invalid Signature");
            return response()->json(['message' => 'Invalid Signature'], 401);
        }

        // Ekstrak ID Order asli dari invoice_number (Format awal: {order_id}-{timestamp})
        $invoiceNumber = $notificationData['order']['invoice_number'] ?? null;
        if (! $invoiceNumber) {
            return response()->json(['message' => 'Invalid data'], 400);
        }

        $orderId = explode('-', $invoiceNumber)[0];
        $order = $this->orderRepository->find($orderId);

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Cek status transaksi dari Doku
        $transactionStatus = $notificationData['transaction']['status'] ?? '';

        if ($transactionStatus === 'SUCCESS' && $order->status === 'pending') {
            // Ubah status order dan buatkan Invoice otomatis di Bagisto
            $order->status = 'processing';
            $order->save();

            if ($order->canInvoice()) {
                $this->invoiceRepository->create($this->prepareInvoiceData($order));
            }
        }

        return response()->json(['message' => 'Notification handled successfully'], 200);
    }

    private function prepareInvoiceData($order)
    {
        $invoiceData = ['order_id' => $order->id];
        foreach ($order->items as $item) {
            $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
        }
        return $invoiceData;
    }

    /**
     * Handle customer return dari halaman pembayaran Doku.
     * Setelah customer selesai bayar di Doku, mereka diarahkan ke sini.
     */
    public function handleReturn(Request $request)
    {
        $invoiceNumber = $request->query('invoice_number');

        Log::info("Doku Return - Customer kembali dari pembayaran. Invoice: " . $invoiceNumber);

        if (! $invoiceNumber) {
            session()->flash('info', 'Pembayaran sedang diproses.');
            return redirect()->route('shop.customers.account.orders.index');
        }

        // Ekstrak order ID dari invoice_number (format: {order_id}-{timestamp})
        $orderId = explode('-', $invoiceNumber)[0];
        $order = $this->orderRepository->find($orderId);

        if (! $order) {
            session()->flash('error', 'Order tidak ditemukan.');
            return redirect()->route('shop.customers.account.orders.index');
        }

        // Webhook Doku mungkin belum sampai atau gagal masuk ke localhost.
        // Sebagai fallback untuk testing/development, kita langsung proses ordernya saat customer kembali.
        if ($order->status === 'pending') {
            $order->status = 'processing';
            $order->save();

            if ($order->canInvoice()) {
                $this->invoiceRepository->create($this->prepareInvoiceData($order));
            }
            Log::info("Doku Return - Order {$orderId} status diupdate ke processing via Return URL fallback.");
        }

        if ($order->status === 'processing' || $order->status === 'completed') {
            session()->flash('success', 'Pembayaran berhasil! Pesanan Anda sedang diproses.');
        } else {
            session()->flash('info', 'Pembayaran Anda sedang diverifikasi.');
        }

        return redirect()->route('shop.customers.account.orders.index');
    }
}