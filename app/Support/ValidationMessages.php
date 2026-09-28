<?php

namespace App\Support;

/**
 * Every user-facing validation string in one place.
 *
 * The house rule this file exists to enforce: a message must answer "what is
 * wrong" and "how do I fix it" on its own, without a customer ever needing to
 * know that a "field" or a "rule" exists. Vague wording such as "Invalid
 * input." or "Validation failed." is banned, so if a new rule is added its
 * message belongs here rather than being left to the framework default.
 *
 * Messages are deliberately short and say the expected shape outright:
 * "Quantity must be at least 1." beats "The quantity must be greater than 0."
 * because the second one describes the rule rather than the fix.
 */
final class ValidationMessages
{
    /* -----------------------------------------------------------------
     | Customer checkout
     * -------------------------------------------------------------- */

    /**
     * The name is a person's name, so the character rule is permissive on
     * purpose: letters of any alphabet, marks, spaces, hyphens, apostrophes
     * and periods are all ordinary in real names and must not be rejected.
     *
     * The `regex:` prefix is required — a bare `/pattern/` is read as a rule
     * name and throws at validation time.
     *
     * @return array<int, string>
     */
    public static function customerNameCharacters(): array
    {
        return ['regex:/^[\p{L}\p{M}\p{Zs} .\'\-]+$/u'];
    }

    /** @return array<string, string> */
    public static function customerName(): array
    {
        return [
            'customer_name.required' => 'Please enter your name.',
            'customer_name.string' => 'Please enter your name.',
            'customer_name.max' => 'Name must not exceed 100 characters.',
            'customer_name.regex' => 'Name can contain letters, spaces, hyphens and apostrophes only.',
        ];
    }

    /** @return array<string, string> */
    public static function serviceType(): array
    {
        return [
            'service_type.required' => 'Please select a service type.',
            'service_type.in' => 'Please select either Dine-in or Takeout.',
        ];
    }

    /** @return array<string, string> */
    public static function cartQuantity(): array
    {
        return [
            'quantity.required' => 'Please enter a quantity.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Quantity must be at least 1. Use Remove to take an item out of your order.',
            'quantity.max' => 'Please order '.config('kapecha.max_per_line').' or fewer of one item at a time.',
        ];
    }

    /* -----------------------------------------------------------------
     | Authentication
     * -------------------------------------------------------------- */

    /**
     * Deliberately says nothing about which half was wrong. Naming the
     * failing field would let anyone discover which usernames exist.
     *
     * @return array<string, string>
     */
    public static function credentials(): array
    {
        return [
            'username.required' => 'Please enter your username.',
            'password.required' => 'Please enter your password.',
        ];
    }

    public static function invalidCredentials(): string
    {
        return 'Invalid username or password.';
    }

    /* -----------------------------------------------------------------
     | Products
     * -------------------------------------------------------------- */

    /** @return array<string, string> */
    public static function product(): array
    {
        return [
            'product_name.required' => 'Enter a product name.',
            'product_name.string' => 'Enter a product name.',
            'product_name.max' => 'Product name must not exceed 100 characters.',
            'product_name.unique' => 'A product with this name already exists.',

            'category.required' => 'Please select a category.',
            'category.in' => 'Please select a category from the list.',

            'price.required' => 'Enter a price.',
            'price.numeric' => 'Enter a price as a number, such as 145.',
            'price.min' => 'Enter a price of 0 or more.',
            'price.max' => 'Price must not exceed 99,999.99.',

            'quantity.required' => 'Enter how many you have in stock.',
            'quantity.integer' => 'Enter stock as a whole number.',
            'quantity.min' => 'Enter a stock of 0 or more.',
            'quantity.max' => 'Stock must not exceed 100,000.',

            'description.max' => 'Description must not exceed 500 characters.',
        ];
    }

    /* -----------------------------------------------------------------
     | Accounts
     * -------------------------------------------------------------- */

    /** @return array<string, string> */
    public static function accountUsername(): array
    {
        return [
            'username.required' => 'Username is required.',
            'username.string' => 'Username is required.',
            'username.max' => 'Username must not exceed 50 characters.',
            'username.regex' => 'Username can only use letters, numbers, dots, hyphens and underscores.',
            'username.unique' => 'That username is already taken.',
        ];
    }

    /** @return array<string, string> */
    public static function accountRole(): array
    {
        return [
            'role.required' => 'Choose a role for this account.',
            'role.in' => 'Choose Admin or Staff.',
        ];
    }

    /** @return array<string, string> */
    public static function accountPassword(): array
    {
        return [
            'password.required' => 'Please enter a password.',
            'password.min' => 'Password must be at least 8 characters.',
            'password_confirmation.required' => 'Please confirm the password.',
            'password_confirmation.same' => 'Passwords do not match.',
        ];
    }

    /* -----------------------------------------------------------------
     | Stock
     * -------------------------------------------------------------- */

    /** The headline shown above the affected field. */
    public static function notEnoughStock(): string
    {
        return 'Not enough stock available.';
    }

    /**
     * The specific line, placed under the quantity that is too large.
     */
    public static function stockAvailable(int $count, string $name): string
    {
        return sprintf(
            'Only %d %s %s currently available.',
            $count,
            $name,
            $count === 1 ? 'item is' : 'items are'
        );
    }

    /* -----------------------------------------------------------------
     | Orders
     * -------------------------------------------------------------- */

    public static function emptyCart(): string
    {
        return 'Your order is empty.';
    }

    public static function emptyCartBody(): string
    {
        return "You haven't added anything to your order yet.";
    }

    /**
     * Used when stock moved while the order sat in the tray. The affected
     * lines are listed alongside this heading.
     */
    public static function stockChanged(): string
    {
        return 'Some items in your order are no longer available in the requested quantity.';
    }

    public static function stockChangedBody(): string
    {
        return 'Please update the quantity before continuing.';
    }

    public static function orderSubmitted(string $number): string
    {
        return "Order submitted successfully. Order #{$number} is now pending.";
    }

    public static function orderUpdated(string $name, int $quantity): string
    {
        return "Order updated successfully. {$name} is now × {$quantity}.";
    }

    public static function orderAddedToCart(string $name, int $quantity): string
    {
        return "Order added to cart. {$name} × {$quantity}.";
    }

    public static function orderProcessed(string $number): string
    {
        return "Order processed successfully. Order #{$number} is Completed and stock has been updated.";
    }

    public static function orderCancelled(string $number): string
    {
        return "Order #{$number} was cancelled. No stock was deducted.";
    }

    /** @return array<string, string> */
    public static function orderProcessing(): array
    {
        return [
            'order.completed' => 'This order has already been completed.',
            'order.cancelled' => 'This order has been cancelled and cannot be processed.',
            'order.not_pending' => 'Only a Pending order can be processed.',
            'order.missing_product' => 'An item on this order no longer exists on the menu.',
            'order.stock' => 'Order cannot be completed because there is not enough stock.',
        ];
    }

    /* -----------------------------------------------------------------
     | Failures that must never leak a stack trace
     * -------------------------------------------------------------- */

    public static function transactionFailed(): string
    {
        return 'Order processing failed. No inventory changes were saved.';
    }

    public static function saveFailed(string $thing): string
    {
        return "Unable to save the {$thing}. Please check the information and try again.";
    }

    public static function loggedOut(): string
    {
        return 'Logged out successfully.';
    }
}
