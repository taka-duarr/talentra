<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentProofController extends Controller
{
    /**
     * Payment receipts live on the private disk; only the buyer and admins may open them.
     */
    public function __invoke(Request $request, Order $order): StreamedResponse
    {
        $user = $request->user();

        abort_unless($order->user_id === $user->id || $user->isSuperAdmin(), 403);
        abort_unless($order->proof_path && Storage::disk('local')->exists($order->proof_path), 404);

        return Storage::disk('local')->response($order->proof_path);
    }
}
