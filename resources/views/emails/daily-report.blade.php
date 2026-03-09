<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Rapport quotidien</title></head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #2c3e50;">📊 Rapport J-1 — {{ $report['date'] }}</h1>

    <h2 style="border-bottom: 2px solid #3498db; padding-bottom: 8px;">🏆 Produits les plus / moins vendus</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <tr style="background: #ecf0f1;">
            <th style="padding: 8px; border: 1px solid #ddd;">Catégorie</th>
            <th style="padding: 8px; border: 1px solid #ddd;">Produit</th>
            <th style="padding: 8px; border: 1px solid #ddd;">Quantité</th>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;">⬆ Le plus vendu</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $report['best_qty']['produit'] ?? 'N/A' }}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $report['best_qty']['quantite'] ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;">⬇ Le moins vendu</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $report['worst_qty']['produit'] ?? 'N/A' }}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $report['worst_qty']['quantite'] ?? '—' }}</td>
        </tr>
    </table>

    <h2 style="border-bottom: 2px solid #3498db; padding-bottom: 8px; margin-top: 30px;">💰 CA max / min</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <tr style="background: #ecf0f1;">
            <th style="padding: 8px; border: 1px solid #ddd;">Catégorie</th>
            <th style="padding: 8px; border: 1px solid #ddd;">Produit</th>
            <th style="padding: 8px; border: 1px solid #ddd;">CA</th>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;">⬆ CA max</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $report['best_ca']['produit'] ?? 'N/A' }}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ isset($report['best_ca']['ca']) ? number_format($report['best_ca']['ca'], 2).' €' : '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;">⬇ CA min</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $report['worst_ca']['produit'] ?? 'N/A' }}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ isset($report['worst_ca']['ca']) ? number_format($report['worst_ca']['ca'], 2).' €' : '—' }}</td>
        </tr>
    </table>

    <h2 style="border-bottom: 2px solid #3498db; padding-bottom: 8px; margin-top: 30px;">🌍 CA par site</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <tr style="background: #ecf0f1;">
            <th style="padding: 8px; border: 1px solid #ddd;">Site</th>
            <th style="padding: 8px; border: 1px solid #ddd;">CA</th>
        </tr>
        @foreach($report['ca_par_site'] as $site)
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;">nutri-sport.{{ $site['site'] }}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ number_format($site['ca'], 2) }} €</td>
        </tr>
        @endforeach
    </table>

    <hr style="border-color: #eee; margin: 30px 0;">
    <p style="color: #999; font-size: 12px;">Rapport généré automatiquement — NutriSport</p>
</body>
</html>
