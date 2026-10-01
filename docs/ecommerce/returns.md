# Returns (RMA)

Customers can ask to send items back, and staff handle the requests under **Sales → Returns**, which needs the permission `sales.returns.manage`.

## Customers

The order page (for the customer, or the browser with the order's email link) shows a **Request a return** button while returns are possible:

- **When:** the order has shipped (fully or partly) and is not cancelled.
- **Return period:** it lasts *Return period* days after the last shipment (Admin → Settings → Returns, default 14). In the EU, consumers have at least 14 days.
- **What:** only units that were shipped, are not refunded, and are not in another open request.

The customer chooses the quantities, a reason (damaged or faulty, wrong item, not as described, no longer needed, other) and an optional note. Each request gets a number (`RMA-000001`; the prefix is a setting) and appears on the order page with its status.

Headless clients use the Store API:

- **Reading:** `GET /orders/{id}` includes `returns` and `returnable` (the allowed units per order line, reasons and deadline).
- **Requesting:** `POST /orders/{id}/returns`.

## Staff

| Status | Next steps |
|---|---|
| Requested | **Approve** (optionally with instructions, e.g. where to send the parcel) or **Reject** (with a reason) |
| Approved | **Mark received**: units that arrived, and whether they go back into stock. Or **Close** if nothing came back |
| Received | **Refund received items**: refunds the paid price of the received units through the order's payment. Or **Close** for an exchange or repair handled outside the shop |
| Refunded | **Close** |

Each step works like the rest of the order system:

- **Order history:** every step is written to the order's history and the activity log.
- **Customer emails:** the customer gets an email at each step, in the order's language (setting *Emails → Return request updates*). A refund sends the usual refund email instead.
- **Stock:** received units put back into stock are recorded in the stock history (reason *Return*, with the return number).
- **Refund amounts:** they come from `RefundService`, so they follow the same rules as manual refunds, including promotion discounts and tax added on top. The refund does not restock again.

The Admin API offers `GET /returns`, `GET /returns/{id}` and `POST /returns/{id}/transitions` (`action`: approve, reject, receive, refund, close).

## For developers

`PnShop\Returns\ReturnService` holds the rules:

- `eligibility()`;
- `request()`;
- `approve()` and `reject()`;
- `receive()`;
- `refund()`;
- `close()`.

`ReturnStatus` declares the allowed transitions. Requests are created under a lock on the order, so two submissions cannot claim the same units. Exchanges, return shipping labels and store credit are not built in yet.
