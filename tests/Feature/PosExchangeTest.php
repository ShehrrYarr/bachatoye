<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ReturnOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsShopData;
use Tests\TestCase;

/** Bug #5: exchanges need the returns permission, can't repeat, and cap the trade-in. */
class PosExchangeTest extends TestCase
{
    use RefreshDatabase, BuildsShopData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin    = $this->makeAdmin();
        $this->salesman = $this->makeUser('salesman', ['pos.access', 'pos.process_returns']);

        $this->sold     = $this->makeProduct(['price' => 1000, 'stock_quantity' => 5]);
        $this->newItem  = $this->makeProduct(['price' => 1200, 'stock_quantity' => 5]);
        $this->newColor = $this->makeColor($this->newItem, 5, 'Blue');

        $res = $this->posSale($this->admin, [['product_id' => $this->sold->id, 'quantity' => 1, 'unit_price' => 1000]])->assertOk();
        $this->order = Order::with('items')->find($res->json('order_id'));
    }

    private function exchange($user, float $tradeIn = 1000)
    {
        return $this->actingAs($user)->postJson(route('pos.exchange.process'), [
            'original_order_id' => $this->order->id,
            'return_item_id'    => $this->order->items->first()->id,
            'return_quantity'   => 1,
            'exchange_value'    => $tradeIn,
            'new_items'         => [[
                'product_id' => $this->newItem->id, 'quantity' => 1, 'unit_price' => 1200, 'color_id' => $this->newColor->id,
            ]],
            'payment_method'    => 'cash',
        ]);
    }

    public function test_pos_user_without_returns_permission_cannot_exchange(): void
    {
        $noReturns = $this->makeUser('salesman', ['pos.access']);

        $this->exchange($noReturns)->assertForbidden();
        $this->actingAs($noReturns)->get(route('pos.exchange.index'))->assertForbidden();
        $this->assertSame(0, ReturnOrder::count());
    }

    public function test_exchanging_the_same_item_twice_is_rejected_and_stock_unchanged(): void
    {
        $this->exchange($this->salesman)->assertOk();
        $this->assertSame(5, $this->sold->fresh()->stock_quantity); // 4 after sale, +1 back

        $this->exchange($this->salesman)
            ->assertStatus(422)
            ->assertJsonPath('error', "\"{$this->sold->name}\" has already been fully returned or exchanged.");

        $this->assertSame(5, $this->sold->fresh()->stock_quantity);
        $this->assertSame(1, ReturnOrder::count());
    }

    public function test_exchange_saves_colour_on_the_new_item(): void
    {
        $res = $this->exchange($this->salesman)->assertOk();

        $item = Order::find($res->json('order_id'))->items->first();
        $this->assertSame($this->newColor->id, (int) $item->color_id);
        $this->assertSame(4, $this->newColor->fresh()->stock_quantity);
    }

    public function test_salesman_cannot_give_trade_in_above_price_paid(): void
    {
        $this->exchange($this->salesman, 1500)->assertStatus(422);
        $this->assertSame(0, ReturnOrder::count());

        $this->exchange($this->salesman, 1000)->assertOk(); // exactly what was paid is fine
    }

    public function test_admin_can_give_trade_in_above_price_paid(): void
    {
        $this->exchange($this->admin, 1500)->assertOk();
    }
}
