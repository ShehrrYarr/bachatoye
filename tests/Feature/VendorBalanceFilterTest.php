<?php

namespace Tests\Feature;

use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsShopData;
use Tests\TestCase;

/** Vendors list: "They owe us" / "We owe them" filters and totals. */
class VendorBalanceFilterTest extends TestCase
{
    use RefreshDatabase, BuildsShopData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->makeAdmin();

        Vendor::create(['name' => 'Owes Us One', 'balance' => -8000]);
        Vendor::create(['name' => 'Owes Us Two', 'balance' => -2000]);
        Vendor::create(['name' => 'We Owe Them', 'balance' => 5000]);
        Vendor::create(['name' => 'All Settled', 'balance' => 0]);
    }

    private function list(array $query = [])
    {
        return $this->actingAs($this->admin)->get(route('admin.vendors.index', $query))->assertOk();
    }

    public function test_they_owe_us_shows_only_negative_balances(): void
    {
        $this->list(['balance' => 'owes_us'])
            ->assertSee('Owes Us One')->assertSee('Owes Us Two')
            ->assertDontSee('We Owe Them')->assertDontSee('All Settled');
    }

    public function test_we_owe_them_shows_only_positive_balances(): void
    {
        $this->list(['balance' => 'we_owe'])
            ->assertSee('We Owe Them')
            ->assertDontSee('Owes Us One')->assertDontSee('All Settled');
    }

    public function test_old_outstanding_link_still_means_we_owe_them(): void
    {
        $this->list(['balance' => 'outstanding'])->assertSee('We Owe Them')->assertDontSee('Owes Us One');
    }

    public function test_totals_and_plain_word_labels(): void
    {
        $this->list()
            ->assertSee('Rs. 10,000')->assertSee('Vendors owe us · 2 vendors')
            ->assertSee('Rs. 5,000')->assertSee('We owe vendors · 1 vendor')
            ->assertSee('Owes us Rs. 8,000')->assertSee('We owe Rs. 5,000')
            ->assertDontSee('Cr. Rs.');
    }
}
