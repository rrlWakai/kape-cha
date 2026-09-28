<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\StockLimitException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CartService;
use App\Support\ValidationMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index(): View
    {
        return view('customer.cart', [
            'items' => $this->cart->items(),
            'total' => $this->cart->total(),
            'cartCount' => $this->cart->count(),
            // Lines whose stock moved while the order waited, so the cart can
            // name exactly which quantity needs correcting.
            'unavailable' => $this->unavailableLines(),
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $validated = $this->validateQuantity($request);

        $productId = (int) $validated['product_id'];
        $quantity = (int) $validated['quantity'];

        try {
            $this->cart->add($productId, $quantity);
        } catch (StockLimitException $e) {
            throw $this->stockException($e);
        }

        return redirect()
            ->route('menu')
            ->with('success', ValidationMessages::orderAddedToCart(
                Product::find($productId)?->product_name ?? 'Item',
                $quantity,
            ))
            ->with('added_product_id', $productId)
            ->with('notice_action', [
                'label' => 'View order',
                'url' => route('cart.index'),
                'method' => 'get',
            ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $this->validateQuantity($request);

        $productId = (int) $validated['product_id'];
        $quantity = (int) $validated['quantity'];

        $name = Product::find($productId)?->product_name ?? 'Item';

        try {
            $this->cart->update($productId, $quantity);
        } catch (StockLimitException $e) {
            throw $this->stockException($e);
        }

        return redirect()
            ->route('cart.index')
            ->with('success', ValidationMessages::orderUpdated($name, $quantity));
    }

    public function remove(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,product_id'],
        ]);

        $productId = (int) $validated['product_id'];
        $item = $this->cart->items()->firstWhere('product.product_id', $productId);
        $name = $item['product']->product_name ?? 'Item';

        // Keep the line just long enough for the Undo button to be honest.
        $request->session()->put('cart_removed', [
            'product_id' => $productId,
            'quantity' => $item['quantity'] ?? 1,
            'name' => $name,
        ]);

        $this->cart->remove($productId);

        return redirect()
            ->route('cart.index')
            ->with('success', "{$name} was removed from your order.")
            ->with('notice_action', [
                'label' => 'Undo',
                'url' => route('cart.restore'),
                'method' => 'post',
            ]);
    }

    public function restore(Request $request): RedirectResponse
    {
        $removed = $request->session()->pull('cart_removed');

        if (! $removed) {
            return redirect()
                ->route('cart.index')
                ->with('warning', 'There is nothing left to undo.');
        }

        try {
            $this->cart->restore($removed['product_id'], $removed['quantity']);
        } catch (StockLimitException $e) {
            return redirect()
                ->route('cart.index')
                ->with('warning', sprintf(
                    'Could not restore %s. %s',
                    $removed['name'],
                    $e->detail(),
                ));
        }

        return redirect()
            ->route('cart.index')
            ->with('success', "{$removed['name']} was put back in your order.");
    }

    /**
     * PHASE 2 — Real order persistence.
     *
     * The order header and its lines are written inside one transaction with
     * status 'Pending'. Inventory is deducted later by OrderProcessingService
     * when staff process the ticket.
     */
    public function checkout(Request $request): RedirectResponse
    {
        // Checked before the empty-cart test on purpose: `items()` cannot see a
        // dish that has been deleted, so a cart holding only vanished lines
        // would otherwise look empty and the order would go through.
        $gone = $this->missingLines();

        if ($gone->isNotEmpty()) {
            throw $this->unavailable($gone);
        }

        if ($this->cart->count() === 0) {
            return redirect()
                ->route('cart.index')
                ->with('error', ValidationMessages::emptyCart());
        }

        $request->merge([
            'customer_name' => is_string($request->input('customer_name'))
                ? trim($request->input('customer_name'))
                : $request->input('customer_name'),
        ]);

        $validated = $request->validate(
            [
                'customer_name' => array_merge(
                    ['required', 'string', 'max:100'],
                    ValidationMessages::customerNameCharacters(),
                ),
                'service_type' => ['required', 'in:Dine-in,Takeout'],
            ],
            array_merge(
                ValidationMessages::customerName(),
                ValidationMessages::serviceType(),
            ),
        );

        $items = $this->cart->items();

        // Stock is re-checked here, not just at add time: another order may
        // have consumed the case while this one sat in the tray. Each affected
        // line is reported with what was asked for and what exists.
        $unavailable = $this->shortLines();

        if ($unavailable->isNotEmpty()) {
            throw $this->unavailable($unavailable);
        }

        try {
            $order = DB::transaction(function () use ($validated, $items) {
                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'customer_name' => $validated['customer_name'],
                    'service_type' => $validated['service_type'],
                    'status' => 'Pending',
                    'total_amount' => $items->sum('subtotal'),
                    'order_date' => now(),
                ]);

                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id' => $order->order_id,
                        'product_id' => $item['product']->product_id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['product']->price,
                        'subtotal' => $item['subtotal'],
                    ]);
                }

                return $order;
            });
        } catch (\Throwable $e) {
            // The transaction rolled itself back, so nothing was written. The
            // customer is told that plainly and the technical detail is logged.
            Log::error('Checkout failed and was rolled back.', ['exception' => $e]);

            return redirect()
                ->route('cart.index')
                ->with('error', 'Unable to complete the order. Nothing was changed. Please try again.');
        }

        // Keep a lightweight reference for the confirmation receipt.
        $request->session()->put('last_order_id', $order->order_id);
        $this->cart->clear();
        $request->session()->forget('cart_removed');

        return redirect()
            ->route('cart.confirmation')
            ->with('success', ValidationMessages::orderSubmitted($order->order_number));
    }

    public function confirmation(Request $request): View
    {
        $orderId = $request->session()->pull('last_order_id');

        $order = $orderId
            ? Order::with('items.product')->find($orderId)
            : null;

        return view('customer.confirmation', ['order' => $order, 'cartCount' => 0]);
    }

    /**
     * Shared validation for both quantity entry points.
     *
     * The messages come from the house vocabulary so that "Quantity must be
     * at least 1." reads the same whether the number was typed into the menu
     * or into an existing order line.
     */
    private function validateQuantity(Request $request): array
    {
        return $request->validate(
            [
                'product_id' => ['required', 'integer', 'exists:products,product_id'],
                'quantity' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:'.config('kapecha.max_per_line'),
                ],
            ],
            ValidationMessages::cartQuantity() + [
                'product_id.required' => 'Please choose an item from the menu.',
                'product_id.exists' => 'That item is no longer on the menu. Please choose another.',
            ],
        );
    }

    /**
     * Turn a stock refusal into a headline plus a field error.
     *
     * Both are needed: the headline says a limit was hit at all, and the
     * field error gives the number so the quantity can be corrected without
     * guessing. The submitted product id travels in old() so the message
     * appears on the offending line only, and the other lines of the order
     * keep whatever the customer already entered.
     */
    private function stockException(StockLimitException $e): ValidationException
    {
        session()->flash('warning', $e->headline());

        return ValidationException::withMessages([
            'quantity' => [$e->detail(), sprintf('You asked for %d.', $e->requested)],
        ]);
    }

    /**
     * Lines in the cart whose requested quantity no longer exists.
     *
     * @return Collection<int, array{product_id:int, name:string, requested:int, available:int, missing:bool}>
     */
    private function shortLines()
    {
        return $this->cart->items()
            ->filter(fn ($item) => $item['quantity'] > $item['product']->quantity)
            ->map(fn ($item) => [
                'product_id' => $item['product']->product_id,
                'name' => $item['product']->product_name,
                'requested' => $item['quantity'],
                'available' => $item['product']->quantity,
                'missing' => false,
            ])
            ->values();
    }

    /**
     * Lines whose product has been removed from the menu entirely.
     *
     * `items()` cannot report these, because leaving them out is exactly what
     * it does. They are described by id, since there is no longer a name, a
     * price or a photo to describe them with.
     *
     * @return Collection<int, array{product_id:int, name:string, requested:int, available:int, missing:bool}>
     */
    private function missingLines()
    {
        return collect($this->cart->missingLines())
            ->map(fn (array $line) => [
                'product_id' => $line['product_id'],
                'name' => 'Item #'.$line['product_id'],
                'requested' => $line['quantity'],
                'available' => 0,
                'missing' => true,
            ])
            ->values();
    }

    /**
     * Every line that cannot be ordered as it stands, in either direction.
     *
     * @return Collection<int, array{product_id:int, name:string, requested:int, available:int, missing:bool}>
     */
    private function unavailableLines()
    {
        return $this->shortLines()->concat($this->missingLines())->values();
    }

    /**
     * Refuse an order, naming each dish and the real number available.
     *
     * Every affected line gets its own field error, so the customer can correct
     * one quantity rather than guess which one moved. A headline says a limit
     * was hit at all. `back()` keeps the name and service type already typed,
     * so fixing a quantity never means retyping the ticket.
     *
     * @param  Collection<int, array{product_id:int, name:string, requested:int, available:int, missing:bool}>  $lines
     */
    private function unavailable($lines): ValidationException
    {
        $errors = $lines
            ->mapWithKeys(fn (array $line) => [
                'quantity.'.$line['product_id'] => [
                    $line['missing']
                        ? sprintf('%s is no longer on the menu. Please remove it to continue.', $line['name'])
                        : sprintf(
                            'Only %d %s items are currently available.',
                            $line['available'],
                            $line['name'],
                        ),
                    sprintf('You asked for %d.', $line['requested']),
                ],
            ])
            ->all();

        session()->flash('error', ValidationMessages::stockChanged());

        return ValidationException::withMessages($errors);
    }
}
