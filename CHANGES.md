# Treklite – changes in this version

## After replacing your files, run (in the project folder)
```
php artisan migrate
php artisan storage:link
php artisan config:clear
```
(`storage:link` is only needed once – it makes product photos and payment photos visible.)

## What changed
**Dashboard** – Out of stock / Low stock panels show 5 rows and scroll for the rest. Latest stock movements and Latest sales show 5 per page with Previous/Next.

**New Sale** – Amount received is required for every payment method and must be at least the total (more is fine, change is shown). "Complete Sale" stays disabled until it is. Product grid scrolls inside its own box.

**Payment** – Cash, GCash, Bank Transfer only. GCash / Bank Transfer need a photo of the confirmation and the amount; no reference number. The same photo cannot be used on two sales.

**Sales Records** – search (invoice / customer / contact), date range with Today / This week / This month, cashier (Owner), payment method, running total.

**Inventory** – opens as brand cards; click a brand to see its items; filters for category, size, color, status and search. One form per item: number + Receive/Count + Submit. A count that differs from the system asks for a note and a confirmation; expected vs counted is saved. New **Stock History** page (also per item, and "only counts that did not match"). Sales now appear in stock history too.

**Products** – "Total Stock" replaced by **Manage Inventory** (opens Inventory for that product). Search + brand + category filters. Brand/category badges. SKU is optional (blank = internal code like TL-0007-K3QZ). Brand/category fields suggest existing values.

**Also fixed** – seeder crash (undefined `$products`), timezone set to Asia/Manila, pagination now uses Bootstrap styling.

## Notes
- Old sales paid with PayMaya / Card still display, labelled "(old)".
- Times of sales saved before this update were stored as UTC, so they will show 8 hours earlier than the real time. New sales are correct.
