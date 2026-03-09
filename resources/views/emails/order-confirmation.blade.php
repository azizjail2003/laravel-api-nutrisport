<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Confirmation de commande</title></head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #e67e22;">
        @if($recipient === 'admin')
            🛒 Nouvelle commande #{{ $order->id }}
        @else
            ✅ Commande #{{ $order->id }} confirmée
        @endif
    </h1>

    <p>Bonjour {{ $recipient === 'admin' ? 'Administrateur' : $user->name }},</p>

    @if($recipient === 'client')
        <p>Nous avons bien reçu votre commande. Elle sera traitée dans les plus brefs délais.</p>
    @else
        <p>Une nouvelle commande a été passée sur le site <strong>{{ $order->site->name ?? '' }}</strong>.</p>
    @endif

    <h2 style="border-bottom: 2px solid #e67e22; padding-bottom: 8px;">Détails de la commande</h2>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8f8f8;">
                <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Produit</th>
                <th style="padding: 8px; text-align: center; border: 1px solid #ddd;">Qté</th>
                <th style="padding: 8px; text-align: right; border: 1px solid #ddd;">Prix unit.</th>
                <th style="padding: 8px; text-align: right; border: 1px solid #ddd;">Sous-total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $item->product->name ?? '—' }}</td>
                <td style="padding: 8px; text-align: center; border: 1px solid #ddd;">{{ $item->quantity }}</td>
                <td style="padding: 8px; text-align: right; border: 1px solid #ddd;">{{ number_format($item->unit_price, 2) }} €</td>
                <td style="padding: 8px; text-align: right; border: 1px solid #ddd;">{{ number_format($item->quantity * $item->unit_price, 2) }} €</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #fff3e0; font-weight: bold;">
                <td colspan="3" style="padding: 8px; text-align: right; border: 1px solid #ddd;">Total</td>
                <td style="padding: 8px; text-align: right; border: 1px solid #ddd;">{{ number_format($order->total, 2) }} €</td>
            </tr>
        </tfoot>
    </table>

    <h2 style="border-bottom: 2px solid #e67e22; padding-bottom: 8px;">Adresse de livraison</h2>
    <p>
        {{ $order->shipping_full_name }}<br>
        {{ $order->shipping_address }}<br>
        {{ $order->shipping_city }}, {{ $order->shipping_country }}
    </p>

    <p><strong>Mode de paiement :</strong> Virement bancaire</p>
    <p><strong>Statut :</strong> {{ ucfirst($order->status) }}</p>

    <hr style="border-color: #eee; margin: 30px 0;">
    <p style="color: #999; font-size: 12px;">NutriSport — nutri-sport.fr | nutri-sport.it | nutri-sport.be</p>
</body>
</html>
