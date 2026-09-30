<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnOrder;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsShopData;
use Tests\TestCase;

/** Sales to vendors: Sales column on the list, Sale History on the vendor page. */
class VendorSalesTest extends TestCase
{
    use RefreshDatabase, BuildsShopData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin  = $this->makeAdmin();
        $this->vendor = Vendor::create(['name' => 'Asif Mobile Karachi', 'balance' => -6000]);
    }

    private function sale(float $total, string $method, ?float $paid = null, array $items = [['Charger', 2]]): Order
    {
        $order = Order::create([
            'source' => 'pos', 'vendor_id' => $this->vendor->id,
            'customer_name' => $this->vendor->name, 'customer_phone' => '-',
            'subtotal' => $total, 'total' => $total, 'amount_paid' => $paid,
            'payment_method' => $method, 'payment_status' => 'paid', 'status' => 'delivered',
        ]);
        foreach ($items as [$name, $qty]) {
            OrderItem::create([
                'order_id' => $order->id, 'product_name' => $name, 'unit_price' => 100,
                'quantity' => $qty, 'line_total' => 100 * $qty,
            ]);
        }
        return $order;
    }

    public function test_vendor_list_shows_sales_count_excluding_deleted_sales(): void
    {
        $this->sale(1000, 'cash');
        $this->sale(2000, 'khata', 0);
        $this->sale(500, 'cash')->delete();

        $html = $this->actingAs($this->admin)->get(route('admin.vendors.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/bg-green-100 text-green-700">2</', $html);
    }

    public function test_vendor_page_shows_sale_history_with_khata_and_returns(): void
    {
        $partial = $this->sale(10000, 'partial', 4000, [['AirPods Pro2', 2], ['Charger', 1], ['Cable', 3]]);
        $khata   = $this->sale(2000, 'khata', 0);
        $cash    = $this->sale(1500, 'cash');
        $deleted = $this->sale(999, 'cash');
        $deleted->delete();

        ReturnOrder::create([
            'order_id' => $partial->id, 'vendor_id' => $this->vendor->id, 'refund_amount' => 1200,
            'refund_method' => 'khata_credit', 'status' => 'completed', 'restock' => true,
        ]);

        $this->actingAs($this->admin)->get(route('admin.vendors.show', $this->vendor))->assertOk()
            ->assertSee('Sale History')
            ->assertSee($partial->order_number)->assertSee($khata->order_number)->assertSee($cash->order_number)
            ->assertDontSee($deleted->order_number)
            // partial: Rs 4,000 paid, Rs 6,000 on khata, 1 return
            ->assertSee('Rs. 6,000')->assertSee('Returned Rs. 1,200')
            ->assertSee('AirPods Pro2 x2, Charger x1')->assertSee('+1 more')
            ->assertSee('Partial')->assertSee('Khata')
            // summary card
            ->assertSee('Total Sales')->assertSee('Rs. 13,500')
            ->assertSee('Vendor owes you')->assertDontSee('(overpaid)');
    }

    public function test_sale_history_is_paginated_separately_from_purchases(): void
    {
        $orders = collect(range(1, 16))->map(fn() => $this->sale(100, 'cash'));
        $oldest = $orders->first()->order_number;
        $newest = $orders->last()->order_number;

        $this->actingAs($this->admin)->get(route('admin.vendors.show', $this->vendor))
            ->assertSee($newest)->assertDontSee($oldest . '<');

        $this->actingAs($this->admin)->get(route('admin.vendors.show', [$this->vendor, 'sales_page' => 2]))
            ->assertSee($oldest)->assertDontSee($newest . '<');
    }

    public function test_salesman_without_orders_view_sees_sales_but_no_order_links(): void
    {
        $sale     = $this->sale(1000, 'cash');
        $salesman = $this->makeUser('salesman', ['vendors.view']);

        $this->actingAs($salesman)->get(route('salesman.vendors.show', $this->vendor))->assertOk()
            ->assertSee($sale->order_number)
            ->assertDontSee(route('salesman.orders.show', $sale));
    }
}
