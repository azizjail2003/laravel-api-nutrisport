<?php

namespace App\Console\Commands;

use App\Mail\DailyReportMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendDailyReport extends Command
{
    protected $signature   = 'nutrisport:daily-report';
    protected $description = 'Envoie le rapport J-1 à l\'administrateur (produits, CA par site).';

    public function handle(): int
    {
        $yesterday = now()->subDay()->toDateString();

        // Orders from yesterday
        $orderItemsYesterday = OrderItem::select(
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as ca')
            )
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereDate('orders.created_at', $yesterday)
            ->groupBy('order_items.product_id')
            ->with('product')
            ->get();

        // Best / worst sellers
        $sorted      = $orderItemsYesterday->sortByDesc('total_qty');
        $bestQty     = $sorted->first();
        $worstQty    = $sorted->last();

        // Best / worst CA
        $sortedCa    = $orderItemsYesterday->sortByDesc('ca');
        $bestCa      = $sortedCa->first();
        $worstCa     = $sortedCa->last();

        // CA per site
        $caPerSite = Order::select('site_id', DB::raw('SUM(total) as ca'))
            ->whereDate('created_at', $yesterday)
            ->groupBy('site_id')
            ->with('site')
            ->get()
            ->map(fn($o) => [
                'site' => $o->site->code ?? $o->site_id,
                'ca'   => round((float) $o->ca, 2),
            ]);

        $report = [
            'date'        => $yesterday,
            'best_qty'    => $bestQty  ? ['produit' => $bestQty->product->name,  'quantite' => $bestQty->total_qty]  : null,
            'worst_qty'   => $worstQty ? ['produit' => $worstQty->product->name, 'quantite' => $worstQty->total_qty] : null,
            'best_ca'     => $bestCa   ? ['produit' => $bestCa->product->name,   'ca' => round($bestCa->ca, 2)]      : null,
            'worst_ca'    => $worstCa  ? ['produit' => $worstCa->product->name,  'ca' => round($worstCa->ca, 2)]    : null,
            'ca_par_site' => $caPerSite->values(),
        ];

        Mail::to(config('mail.admin_address', 'admin@nutrisport.fr'))
            ->send(new DailyReportMail($report));

        $this->info("Rapport J-1 ({$yesterday}) envoyé à l'administrateur.");

        return self::SUCCESS;
    }
}
