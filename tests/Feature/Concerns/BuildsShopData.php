<?php

namespace Tests\Feature\Concerns;

use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

trait BuildsShopData
{
    protected function makeUser(string $role, array $permissions = []): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findOrCreate($role, 'web'));

        foreach ($permissions as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        return $user->fresh();
    }

    protected function makeAdmin(): User
    {
        // Admin gets POS and returns access through the role, like production
        $role = Role::findOrCreate('admin', 'web');
        foreach (['pos.access', 'pos.process_returns'] as $name) {
            $role->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        return $this->makeUser('admin');
    }

    protected function makeProduct(array $attrs = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'test-cat'], ['name' => 'Test Cat']);

        return Product::create(array_merge([
            'name'            => 'Test Product ' . uniqid(),
            'price'           => 1000,
            'cost_price'      => 600,
            'stock_quantity'  => 10,
            'category_id'     => $category->id,
            'is_active'       => true,
            'track_inventory' => true,
            'cod_enabled'     => true,
        ], $attrs))->fresh();
    }

    protected function makeColor(Product $product, int $stock, string $name = 'Red'): ProductColor
    {
        return ProductColor::create([
            'product_id'     => $product->id,
            'name'           => $name,
            'stock_quantity' => $stock,
        ]);
    }

    protected function makeBank(): BankAccount
    {
        return BankAccount::create([
            'label' => 'Test Bank', 'bank_name' => 'HBL', 'account_title' => 'Shop', 'is_active' => true,
        ]);
    }

    /** An online order as checkout leaves it: pending, no stock taken yet. */
    protected function makeEcomOrder(Product $product, int $qty, ?ProductColor $color = null, string $payment = 'cash'): Order
    {
        $order = Order::create([
            'source'         => 'ecommerce',
            'customer_name'  => 'Online Buyer',
            'customer_phone' => '03000000000',
            'subtotal'       => $product->price * $qty,
            'total'          => $product->price * $qty,
            'payment_method' => $payment,
            'payment_status' => 'pending',
            'status'         => 'pending',
        ]);

        OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'color_id'     => $color?->id,
            'color_name'   => $color?->name,
            'unit_price'   => $product->price,
            'cost_price'   => $product->cost_price,
            'quantity'     => $qty,
            'line_total'   => $product->price * $qty,
        ]);

        return $order->fresh();
    }

    /** POST a POS sale; returns the JSON response. */
    protected function posSale(User $user, array $items, array $payment = ['payment_method' => 'cash'])
    {
        return $this->actingAs($user)->postJson(route('pos.order.create'), array_merge(['items' => $items], $payment));
    }
}
