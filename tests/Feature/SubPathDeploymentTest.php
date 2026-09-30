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

    public function test_storefront_search_uses_the_prefix_under_a_sub_path(): void
    {
        $html = $this->withServerVariables($this->subPath())
            ->get('/alzaitoontraders/products')->assertOk()->getContent();

        $this->assertStringContainsString('/alzaitoontraders/api/products/search?q=', $html);
        $this->assertStringNotContainsString('`/api/products/search', $html);
    }
}
