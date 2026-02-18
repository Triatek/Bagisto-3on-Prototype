<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Sales\Repositories\OrderRepository;
use Carbon\Carbon;

class CancelOrderCommand extends Command
{
    protected $signature = 'order:cancel-old';
    protected $description = 'Membatalkan order pending yang lebih dari 1 jam';

    public function __construct(protected OrderRepository $orderRepository)
    {
        parent::__construct();
    }

    public function handle()
    {
        // Cari order pending yang usianya > 60 menit
        $expiredOrders = $this->orderRepository->findWhere([
            ['status', '=', 'pending'],
            ['created_at', '<', Carbon::now()->subMinutes(60)]
        ]);

        foreach ($expiredOrders as $order) {
            $this->orderRepository->cancelExpiredOrder($order);
            $this->info("Order #{$order->id} telah dibatalkan.");
        }
    }
}