# Orders

Orders live in `core/Sales`. Customers see their orders under *Account → Orders*; staff manage them in Admin → Sales → Orders.

## Order number

Each order gets a human number when it is created: the prefix and the order id padded with zeros (`ORD-000042`). Set the prefix and the number of digits in Admin → Settings → Orders. A change applies to new orders only.

## Three states

An order has three independent states. Each is a small state machine, and the admin only offers the transitions allowed from the current state.

| State | Values | Allowed transitions |
|---|---|---|
| **Status** | pending, processing, completed, cancelled | pending → processing / cancelled; processing → completed / cancelled; completed → processing; cancelled → pending (reopen) |
| **Payment** | unpaid, authorized, paid, partially refunded, refunded, failed | unpaid → authorized / paid / failed; authorized → paid / unpaid / failed; paid → partially refunded / refunded; partially refunded → partially refunded / refunded; failed → unpaid / authorized / paid |
| **Fulfillment** | not shipped, partially shipped, shipped, returned | not shipped → partially shipped / shipped; partially shipped → shipped; shipped → returned |

Rules applied on top of the state machines:

- **Payment:** recording a payment on a *pending* order moves it to *processing*.
- **Cancelled orders:** they can't be shipped until they are reopened. Payments and refunds can still be recorded.
- **Stock:**
  - checkout reserves stock;
  - *Shipped* takes it off the shelf;
  - cancelling releases a reservation or puts shipped goods back;
  - reopening reserves the stock again, and fails when it is gone.

## History

Every change is written to the order's **History** tab with the old and new value, an optional note and who made it (staff member, customer or *System*). Checkout writes the first entry. Use *Add note* for anything else worth keeping.

## For developers

```php
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\{OrderStatus, PaymentStatus, FulfillmentStatus};

$workflow = app(OrderWorkflow::class);
$workflow->transition($order, PaymentStatus::Paid, $admin, 'Bank transfer received');
$workflow->transition($order, FulfillmentStatus::Fulfilled);
$workflow->addNote($order, 'Customer asked for evening delivery.', $admin);
```

- Never write `status`, `payment_status` or `fulfillment_status` directly: the workflow checks the transition, moves stock, records history and dispatches events.
- A transition that isn't allowed throws `PnShop\Sales\Exceptions\InvalidOrderTransition`. All order exceptions extend `OrderException`, whose message is safe to show staff.
- **Events (after the transaction commits):**
  - `OrderPlaced($order)` when checkout has created the order;
  - `OrderStateChanged($order, $from, $to, $note)` for every transition.
- The state enums implement Filament's `HasLabel` and `HasColor`, so they render as badges and filter options without extra code.
