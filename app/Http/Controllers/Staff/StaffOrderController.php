<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderProcessingService;
use App\Support\ValidationMessages;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;

class StaffOrderController extends Controller
{
    public function __construct(private OrderProcessingService $processor) {}

    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $statuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];

        // An unrecognised filter falls back to the whole board rather than
        // silently showing everything under a label that lies.
        if ($status !== 'all' && ! in_array($status, $statuses, true)) {
            $status = 'all';
        }

        $orders = Order::with('items.product')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        $counts = ['all' => Order::count()];

        foreach ($statuses as $each) {
            $counts[strtolower($each)] = Order::where('status', $each)->count();
        }

        return view('staff.orders.index', compact('orders', 'counts', 'status'));
    }

    public function show(Order $order): View
    {
        // Route model binding resolves via order_id primary key.
        $order->load('items.product');

        return view('staff.orders.show', compact('order'));
    }

    /**
     * PHASE 2 — ACID commit point for the demonstration:
     * BEGIN → lock order + products → verify Pending + stock → deduct inventory
     * → status Completed + processed_by → COMMIT.
     * Any failure throws and Laravel issues an automatic ROLLBACK.
     */
    public function process(Request $request, Order $order): RedirectResponse
    {
        $order->load('items.product');

        // Admin screens reuse the Staff controller — redirect back to the active prefix.
        $prefix = str_starts_with((string) $request->route()?->getName(), 'admin.') ? 'admin' : 'staff';
        $showRoute = "{$prefix}.orders.show";

        if ($order->status !== 'Pending') {
            return redirect()
                ->route($showRoute, $order)
                ->with('error', $this->blockedReason($order->status))
                ->with('tx_log', [
                    '[BLOCKED] DB::transaction() not entered — orders.status = '.$order->status,
                    'COMMIT/ROLLBACK not attempted. No rows were modified.',
                ]);
        }

        $simulateFailure = $request->boolean('simulate_failure');

        try {
            $this->processor->process($order, $simulateFailure);
        } catch (QueryException $e) {
            // A real database failure. The transaction has already rolled back;
            // the operator is told that, and the driver message stays in the log.
            Log::error('Order processing hit a database error and was rolled back.', [
                'order_number' => $order->order_number,
                'exception' => $e,
            ]);

            return redirect()
                ->route($showRoute, $order)
                ->with('error', ValidationMessages::transactionFailed())
                ->with('tx_log', $this->processor->log())
                ->with('tx_outcome', 'ROLLBACK');
        } catch (RuntimeException $e) {
            // A deliberate refusal: not enough stock, wrong status, or the
            // demonstration rollback. The message is already human-readable.
            return redirect()
                ->route($showRoute, $order)
                ->with('rollback', $e->getMessage())
                ->with('tx_log', $this->processor->log())
                ->with('tx_outcome', 'ROLLBACK');
        }

        $order->refresh();

        return redirect()
            ->route($showRoute, $order)
            ->with('commit', ValidationMessages::orderProcessed($order->order_number))
            ->with('tx_log', $this->processor->log())
            ->with('tx_outcome', 'COMMIT');
    }

    /**
     * One sentence per status, naming the reason rather than restating the
     * badge the operator is already looking at.
     */
    private function blockedReason(string $status): string
    {
        $messages = ValidationMessages::orderProcessing();

        return match ($status) {
            'Completed' => $messages['order.completed'],
            'Cancelled' => $messages['order.cancelled'],
            default => $messages['order.not_pending'],
        };
    }

    /**
     * Withdraw a ticket that has not yet been fulfilled.
     *
     * Only a ticket that is still open can be cancelled: once the counter has
     * committed the transaction the stock has already left the building, so
     * the only honest thing left is to read it back.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $prefix = str_starts_with((string) $request->route()?->getName(), 'admin.') ? 'admin' : 'staff';
        $showRoute = "{$prefix}.orders.show";

        if (! in_array($order->status, ['Pending', 'Processing'], true)) {
            return redirect()
                ->route($showRoute, $order)
                ->with('error', $order->status === 'Cancelled'
                    ? "Order #{$order->order_number} is already cancelled."
                    : "Order #{$order->order_number} has already been completed and can no longer be cancelled.");
        }

        $order->update(['status' => 'Cancelled']);

        return redirect()
            ->route($showRoute, $order)
            ->with('success', ValidationMessages::orderCancelled($order->order_number))
            ->with('tx_log', [
                'UPDATE orders SET status = \'Cancelled\' WHERE order_id = '.$order->order_id.';',
                'No inventory was deducted. The ticket was withdrawn before the counter committed.',
            ])
            ->with('tx_outcome', 'CANCELLED');
    }
}
