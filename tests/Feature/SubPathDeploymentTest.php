<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsShopData;
use Tests\TestCase;

/**
 * The app runs both at a domain root (flashsale.fashion) and behind an Apache
 * Alias (23.230.253.206/alzaitoontraders). Hand-written URLs in views go
 * through @base so they resolve correctly in both.
 */
class SubPathDeploymentTest extends TestCase
{
    use RefreshDatabase, BuildsShopData;

    /** Server variables Apache sets when the app is served from /alzaitoontraders. */
    private function subPath(): array
    {
        return [
            'SCRIPT_NAME'     => '/alzaitoontraders/index.php',
            'SCRIPT_FILENAME' => public_path('index.php'),
            'PHP_SELF'        => '/alzaitoontraders/index.php',
        ];
    }

    public function test_at_domain_root_pos_urls_are_unchanged(): void
    {
        $html = $this->actingAs($this->makeAdmin())->get('/pos')->assertOk()->getContent();

        $this->assertStringContainsString("fetch('/pos/order'", $html);
        $this->assertStringContainsString("register('/sw.js', { scope: '/' })", $html);
        $this->assertStringNotContainsString('@base', $html);
    }

    public function test_under_a_sub_path_pos_urls_carry_the_prefix(): void
    {
        $html = $this->actingAs($this->makeAdmin())
            ->withServerVariables($this->subPath())
            ->get('/alzaitoontraders/pos')->assertOk()->getContent();

        $this->assertStringContainsString("fetch('/alzaitoontraders/pos/order'", $html);
        $this->assertStringContainsString("fetch(`/alzaitoontraders/pos/product/search?q=", $html);
        $this->assertStringContainsString("register('/alzaitoontraders/sw.js', { scope: '/alzaitoontraders/' })", $html);
        $this->assertStringNotContainsString("fetch('/pos/", $html);
    }

    // One sub-path request per test: Laravel's test client keeps request state
    // between calls, so a second sub-path request in the same test 404s.
    public function test_product_create_subcategory_lookup_uses_the_prefix_under_a_sub_path(): void
    {
        $this->actingAs($this->makeAdmin())->withServerVariables($this->subPath())
            ->get('/alzaitoontraders/admin/products/create')->assertOk()
            ->assertSee('fetch(`/alzaitoontraders/admin/categories/${this.categoryId}/subcategories`)', false);
    }

    public function test_product_edit_urls_use_the_prefix_under_a_sub_path(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->makeAdmin())->withServerVariables($this->subPath())
            ->get("/alzaitoontraders/admin/products/{$product->id}/edit")->assertOk()
            ->assertSee('fetch(`/alzaitoontraders/admin/categories/${this.categoryId}/subcategories`)', false)
            ->assertSee("'/alzaitoontraders/admin/products/generate-barcode'", false);
    }

    public function test_purchase_form_urls_use_the_prefix_under_a_sub_path(): void
    {
        $this->actingAs($this->makeAdmin())->withServerVariables($this->subPath())
            ->get('/alzaitoontraders/admin/purchases/create')->assertOk()
            ->assertSee('fetch(`/alzaitoontraders/admin/api/products/search?q=', false)
            ->assertSee("'/alzaitoontraders/admin/api/serials/check'", false)
            ->assertSee("'/alzaitoontraders/admin/purchases/temp-serial-image'", false)
            ->assertSee('fetch(`/alzaitoontraders/admin/api/vendors/${this.vendorId}/balance`)', false);
    }

    public function test_salesman_purchase_form_can_load_vendor_balance(): void
    {
        $salesman = $this->makeUser('salesman', ['purchases.manage']);
        $vendor   = \App\Models\Vendor::create(['name' => 'Test Vendor', 'balance' => -2500]);

        $this->actingAs($salesman)->get('/salesman/purchases/create')->assertOk()
            ->assertSee('fetch(`/salesman/api/vendors/${this.vendorId}/balance`)', false);

        $this->actingAs($salesman)->getJson("/salesman/api/vendors/{$vendor->id}/balance")
            ->assertOk()->assertJson(['balance' => -2500]);

        // Still closed to salesmen who can't record purchases
        $this->actingAs($this->makeUser('salesman', ['vendors.view']))
            ->getJson("/salesman/api/vendors/{$vendor->id}/balance")->assertForbidden();
    }

    public function test_storefront_search_uses_the_prefix_under_a_sub_path(): void
    {
        $html = $this->withServerVariables($this->subPath())
            ->get('/alzaitoontraders/products')->assertOk()->getContent();

        $this->assertStringContainsString('/alzaitoontraders/api/products/search?q=', $html);
        $this->assertStringNotContainsString('`/api/products/search', $html);
    }
}
