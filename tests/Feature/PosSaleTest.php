<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\CashBalanceService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsShopData;
use Tests\TestCase;

/** Bugs #3 (cash double count), #4 (split payments) and #7 (order numbers / offline sync). */
class PosSaleTest extends TestCase
{
    use RefreshDatabase, BuildsShopData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin   = $this->makeAdmin();
        $this->product = $this->makeProduct(['price' => 1930, 'stock_quantity' => 20]);
        $this->bank    = $this->makeBank();
    }

    private function line(float $price = 1930, int $qty = 1): array
    {
        return [['product_id' => $this->product->id, 'quantity' => $qty, 'unit_price' => $price]];
    }

    private function split(float $cash, float $bank): array
    {
        return ['payment_method' => 'split', 'cash_amount' => $cash, 'bank_amount' => $bank, 'bank_account_id' => $this->bank->id];
    }

    // ── #4 split payments ───────────────────────────────────────────────

    public function test_split_overpayment_records_change_out_of_cash(): void
    {
        // The real ORD-2689 case: Rs 480 cash + Rs 1,500 bank on a Rs 1,930 sale
        $res = $this->posSale($this->admin, $this->line(), $this->split(480, 1500))->assertOk();

        $order = Order::find($res->json('order_id'));
        $this->assertEquals(430, (float) $order->cash_amount);
        $this->assertEquals(1500, (float) $order->bank_amount);
        $this->assertEquals(1930, (float) $order->amount_paid);
        $this->assertSame('paid', $order->payment_status);
    }

    public function test_split_short_of_total_is_rejected(): void
    {
        $this->posSale($this->admin, $this->line(), $this->split(400, 1000))
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'Split payment (Rs. 1,400) is less than the total (Rs. 1,930). Use Partial payment to put the rest on khata.']);

        $this->assertSame(0, Order::count());
        $this->assertSame(20, $this->product->fresh()->stock_quantity);
    }

    public function test_split_with_bank_above_total_is_rejected(): void
    {
        $this->posSale($this->admin, $this->line(), $this->split(0, 2000))->assertStatus(422);
        $this->assertSame(0, Order::count());
    }

    public function test_partial_split_overpayment_caps_cash_at_what_was_kept(): void
    {
        $res = $this->posSale($this->admin, $this->line(), [
            'payment_method' => 'partial', 'partial_pay_via' => 'split',
            'cash_amount' => 1000, 'bank_amount' => 1500, 'partial_bank_account_id' => $this->bank->id,
        ])->assertOk();

        $order = Order::find($res->json('order_id'));
        $this->assertEquals(430, (float) $order->cash_amount);
        $this->assertEquals(1930, (float) $order->amount_paid);
    }

    // ── #3 cash balance ─────────────────────────────────────────────────

    public function test_cash_balance_counts_delivered_cod_orders_once(): void
    {
        $this->posSale($this->admin, $this->line(500))->assertOk();

        $online = $this->makeEcomOrder($this->makeProduct(['price' => 1000]), 1);
        $online->update(['status' => 'delivered']);

        $cash = app(CashBalanceService::class)->forShop(null)['cashSummary'];

        $this->assertEquals(500, $cash['in_pos']);
        $this->assertEquals(1000, $cash['in_ecom']);
        $this->assertEquals(1500, $cash['balance']);
    }

    // ── #7 order numbers & offline sync ─────────────────────────────────

    public function test_order_number_comes_from_the_row_id(): void
    {
        $a = Order::find($this->posSale($this->admin, $this->line())->json('order_id'));
        $a->delete(); // soft-deleted numbers must never be reused
        $b = Order::find($this->posSale($this->admin, $this->line())->json('order_id'));

        $this->assertSame('ORD-' . str_pad($a->id, 4, '0', STR_PAD_LEFT), $a->order_number);
        $this->assertSame('ORD-' . str_pad($b->id, 4, '0', STR_PAD_LEFT), $b->order_number);
        $this->assertNotSame($a->order_number, $b->order_number);
    }

    public function test_repeated_offline_sync_returns_the_first_order(): void
    {
        $payload = ['payment_method' => 'cash', 'offline_ref' => 'OFF-123-1'];

        $first  = $this->posSale($this->admin, $this->line(), $payload)->assertOk();
        $second = $this->posSale($this->admin, $this->line(), $payload)->assertOk();

        $this->assertTrue($second->json('already_synced'));
        $this->assertSame($first->json('order_id'), $second->json('order_id'));
        $this->assertSame(1, Order::count());
        $this->assertSame(19, $this->product->fresh()->stock_quantity);
    }

    public function test_offline_replay_of_a_deleted_sale_does_not_recreate_it(): void
    {
        $payload = ['payment_method' => 'cash', 'offline_ref' => 'OFF-123-2'];
        $first   = $this->posSale($this->admin, $this->line(), $payload)->assertOk();
        Order::find($first->json('order_id'))->delete();

        $this->posSale($this->admin, $this->line(), $payload)->assertOk()->assertJson(['already_synced' => true]);

        $this->assertSame(1, Order::withTrashed()->count());
    }

    public function test_database_rejects_a_second_order_with_the_same_offline_ref(): void
    {
        // What stops two simultaneous syncs that both pass the lookup
        $this->posSale($this->admin, $this->line(), ['payment_method' => 'cash', 'offline_ref' => 'OFF-123-3']);

        $this->expectException(UniqueConstraintViolationException::class);
        Order::create([
            'source' => 'pos', 'offline_ref' => 'OFF-123-3', 'customer_name' => 'x', 'customer_phone' => '-',
            'subtotal' => 1, 'total' => 1, 'payment_method' => 'cash', 'payment_status' => 'paid', 'status' => 'delivered',
        ]);
    }
}
