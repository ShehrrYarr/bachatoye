<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsShopData;
use Tests\TestCase;

/** Bugs #6 (cart +/− buttons) and #8 (Setting::get default caching). */
class CartAndSettingTest extends TestCase
{
    use RefreshDatabase, BuildsShopData;

    private function rowId(): string
    {
        return array_key_first(session('cart'));
    }

    public function test_minus_on_last_unit_removes_the_row(): void
    {
        $product = $this->makeProduct();
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        // Exactly what the "−" form posts: a string
        $this->patch(route('cart.update', $this->rowId()), ['quantity' => '0']);

        $this->assertSame([], session('cart'));
    }

    public function test_plus_is_capped_at_available_stock(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 2]);
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);

        $this->patch(route('cart.update', $this->rowId()), ['quantity' => '3'])->assertSessionHasErrors('quantity');

        $this->assertSame(2, session('cart')[$this->rowId()]['quantity']);
    }

    public function test_plus_is_capped_at_colour_stock(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 10]);
        $red     = $this->makeColor($product, 1);
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1, 'color_id' => $red->id]);

        $this->patch(route('cart.update', $this->rowId()), ['quantity' => '2']);

        $this->assertSame(1, session('cart')[$this->rowId()]['quantity']);
    }

    public function test_setting_default_is_not_cached_for_later_callers(): void
    {
        $this->assertSame('150', Setting::get('test_missing_key', '150'));
        $this->assertSame('200', Setting::get('test_missing_key', '200'));

        Setting::set('test_missing_key', '175');
        $this->assertSame('175', Setting::get('test_missing_key', '200'));
    }
}
