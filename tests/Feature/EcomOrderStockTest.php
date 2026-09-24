<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ReturnItem;
use App\Models\ReturnOrder;
use App\Models\SerialNumber;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsShopData;
use Tests\TestCase;

/** Bugs #1 and #2: online order colour stock and status-change stock handling. */
class EcomOrderStockTest extends TestCase
{
    use RefreshDatabase, BuildsShopData;

    private function setStatus(Order $order, string $status)
    {
        return $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.status', $order), ['status' => $status]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->makeAdmin();
    }

    public function test_checkout_saves_colour_and_delivery_deducts_colour_stock(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 5]);
        $red     = $this->makeColor($product, 3);

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2, 'color_id' => $red->id]);
        $this->post(route('checkout.store'), [
            'name' => 'Ali', 'phone' => '03001234567', 'address' => 'Street 1', 'city' => 'Lahore',
            'payment_method' => 'cash',
        ])->assertRedirect();

        $order = Order::where('source', 'ecommerce')->latest('id')->firstOrFail();
        $this->assertSame($red->id, (int) $order->items->first()->color_id);

        $this->setStatus($order, 'delivered')->assertSessionHasNoErrors();

        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->assertSame(1, $red->fresh()->stock_quantity);
    }

    public function test_redelivering_does_not_deduct_stock_twice(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 10]);
        $order   = $this->makeEcomOrder($product, 2);

        $this->setStatus($order, 'delivered');
        $this->setStatus($order, 'processing');
        $this->setStatus($order, 'delivered');

        $this->assertSame(8, $product->fresh()->stock_quantity);
        $this->assertSame(1, StockMovement::where('reference', $order->order_number)->count());
        $this->assertTrue($order->fresh()->stock_deducted);
    }

    public function test_cancelling_a_delivered_order_restores_product_and_colour_stock(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 10]);
        $red     = $this->makeColor($product, 4);
        $order   = $this->makeEcomOrder($product, 3, $red);

        $this->setStatus($order, 'delivered');
        $this->assertSame(7, $product->fresh()->stock_quantity);
        $this->assertSame(1, $red->fresh()->stock_quantity);

        $this->setStatus($order, 'cancelled')->assertSessionHasNoErrors();

        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertSame(4, $red->fresh()->stock_quantity);
        $this->assertFalse($order->fresh()->stock_deducted);

        // Cancelling again (or cancelling a never-delivered order) restores nothing
        $this->setStatus($order, 'pending');
        $this->setStatus($order, 'cancelled');
        $this->assertSame(10, $product->fresh()->stock_quantity);
    }

    public function test_cancelling_an_undelivered_order_does_not_touch_stock(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 10]);
        $order   = $this->makeEcomOrder($product, 2);

        $this->setStatus($order, 'cancelled');

        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertSame(0, StockMovement::where('reference', $order->order_number)->count());
    }

    public function test_returned_status_restores_only_units_not_already_restocked_by_pos_return(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 10]);
        $order   = $this->makeEcomOrder($product, 3);
        $this->setStatus($order, 'delivered');
        $this->assertSame(7, $product->fresh()->stock_quantity);

        // One unit already came back through the POS Return screen (restocked)
        $return = ReturnOrder::create([
            'order_id' => $order->id, 'refund_amount' => 1000, 'refund_method' => 'cash',
            'status' => 'completed', 'restock' => true,
        ]);
        ReturnItem::create([
            'return_id' => $return->id, 'order_item_id' => $order->items->first()->id,
            'product_id' => $product->id, 'product_name' => $product->name,
            'quantity' => 1, 'unit_price' => 1000, 'line_total' => 1000,
        ]);
        $product->increment('stock_quantity'); // what the POS return did

        $this->setStatus($order, 'returned')->assertSessionHasNoErrors();

        $this->assertSame(10, $product->fresh()->stock_quantity); // +2, not +3
    }

    public function test_delivery_is_blocked_when_stock_is_short(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 1]);
        $order   = $this->makeEcomOrder($product, 2);

        $this->setStatus($order, 'delivered')->assertSessionHasErrors('status');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertFalse($order->fresh()->stock_deducted);
        $this->assertSame(1, $product->fresh()->stock_quantity);
    }

    public function test_cancel_releases_used_unit_and_redelivery_reserves_it_again(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 1, 'is_serialized' => true]);
        $serial  = SerialNumber::create([
            'product_id' => $product->id, 'serial_number' => '111122223333444', 'status' => 'in_stock', 'cost_price' => 500,
        ]);
        $order = $this->makeEcomOrder($product, 1);
        $item  = $order->items->first();
        $item->update(['serial_number_id' => $serial->id]);
        $serial->update(['status' => 'sold', 'order_id' => $order->id, 'order_item_id' => $item->id]); // checkout reservation

        $this->setStatus($order, 'cancelled');
        $this->assertSame('in_stock', $serial->fresh()->status);

        $this->setStatus($order, 'delivered')->assertSessionHasNoErrors();
        $this->assertSame('sold', $serial->fresh()->status);
        $this->assertSame($order->id, (int) $serial->fresh()->order_id);
        $this->assertSame(0, $product->fresh()->stock_quantity);
    }

    public function test_redelivery_fails_if_used_unit_was_sold_to_someone_else(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 1, 'is_serialized' => true]);
        $serial  = SerialNumber::create([
            'product_id' => $product->id, 'serial_number' => '555566667777888', 'status' => 'in_stock', 'cost_price' => 500,
        ]);
        $order = $this->makeEcomOrder($product, 1);
        $order->items->first()->update(['serial_number_id' => $serial->id]);

        $other = $this->makeEcomOrder($product, 1);
        $serial->update(['status' => 'sold', 'order_id' => $other->id]);

        $this->setStatus($order, 'delivered')->assertSessionHasErrors('status');
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame($other->id, (int) $serial->fresh()->order_id);
    }
}
