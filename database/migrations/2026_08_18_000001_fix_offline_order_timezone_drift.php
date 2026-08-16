<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Historical offline-synced orders were stored early because
     * offline_created_at (sent by the browser as UTC) was saved without
     * timezone conversion — fixed in PosController::createOrder(). Shift
     * every existing offline-synced order's created_at forward by the app
     * timezone's UTC offset to its true local sale time.
     */
    public function up(): void
    {
        $offsetSeconds = Carbon::now(config('app.timezone'))->getOffset();

        DB::table('orders')->whereNotNull('offline_ref')->orderBy('id')
            ->get(['id', 'created_at'])
            ->each(function ($order) use ($offsetSeconds) {
                DB::table('orders')->where('id', $order->id)->update([
                    'created_at' => Carbon::parse($order->created_at)->addSeconds($offsetSeconds),
                ]);
            });
    }

    public function down(): void
    {
        $offsetSeconds = Carbon::now(config('app.timezone'))->getOffset();

        DB::table('orders')->whereNotNull('offline_ref')->orderBy('id')
            ->get(['id', 'created_at'])
            ->each(function ($order) use ($offsetSeconds) {
                DB::table('orders')->where('id', $order->id)->update([
                    'created_at' => Carbon::parse($order->created_at)->subSeconds($offsetSeconds),
                ]);
            });
    }
};
