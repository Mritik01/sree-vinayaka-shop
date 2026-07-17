<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class RazorpayController extends Controller
{
    // called by the browser right after Razorpay's checkout widget reports success —
    // signature proves the payment actually happened and wasn't forged client-side
    public function verify(Request $request)
    {
        if (\App\Services\ImpersonationService::active()) {
            return response()->json([
                'ok' => false,
                'message' => 'Payment is disabled while viewing this account as an admin.',
            ], 403);
        }

        $data = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $order = $request->user()->orders()
            ->where('razorpay_order_id', $data['razorpay_order_id'])
            ->findOrFail($data['order_id']);

        if ($order->payment_status === 'paid') {
            return response()->json(['ok' => true, 'order_id' => $order->id]);
        }

        $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

        try {
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature' => $data['razorpay_signature'],
            ]);
        } catch (SignatureVerificationError $e) {
            $order->forceFill(['payment_status' => 'failed'])->save();

            return response()->json(['ok' => false, 'message' => 'Payment verification failed.'], 422);
        }

        $order->forceFill([
            'payment_status' => 'paid',
            'razorpay_payment_id' => $data['razorpay_payment_id'],
            'razorpay_signature' => $data['razorpay_signature'],
            'paid_at' => now(),
        ])->save();

        ActivityLogger::log('payment_captured', "Order {$order->orderNumber()} — ₹".number_format($order->total));

        return response()->json(['ok' => true, 'order_id' => $order->id]);
    }

    // Razorpay calls this server-to-server, independent of the customer's browser — so a
    // captured payment still gets recorded even if the tab closes before verify() above fires
    public function webhook(Request $request)
    {
        $secret = config('services.razorpay.webhook_secret');
        $signature = $request->header('X-Razorpay-Signature');

        if (!$secret || !$signature) {
            return response()->json(['ok' => false], 400);
        }

        $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

        try {
            $api->utility->verifyWebhookSignature($request->getContent(), $signature, $secret);
        } catch (SignatureVerificationError $e) {
            return response()->json(['ok' => false], 400);
        }

        $event = $request->input('event');
        $payment = $request->input('payload.payment.entity', []);
        $razorpayOrderId = $payment['order_id'] ?? null;

        if (!$razorpayOrderId) {
            return response()->json(['ok' => true]);
        }

        $order = Order::where('razorpay_order_id', $razorpayOrderId)->first();

        if (!$order || $order->payment_status === 'paid') {
            return response()->json(['ok' => true]);
        }

        if ($event === 'payment.captured') {
            $order->forceFill([
                'payment_status' => 'paid',
                'razorpay_payment_id' => $payment['id'] ?? $order->razorpay_payment_id,
                'paid_at' => now(),
            ])->save();

            ActivityLogger::log('payment_captured', "Order {$order->orderNumber()} — ₹".number_format($order->total));
        } elseif ($event === 'payment.failed') {
            $order->forceFill(['payment_status' => 'failed'])->save();
        }

        return response()->json(['ok' => true]);
    }
}
