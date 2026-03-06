<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfGeneratorService
{
    public function generateCahierChargesPDF(array $data, string $titre): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        // Créer le HTML
        $html = $this->buildPdfHtml($data, $titre);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Générer un nom de fichier unique
        $filename = 'cahier_charges_' . uniqid() . '.pdf';
        
        // Sauvegarder le PDF
        $output = $dompdf->output();
        
        return $output;
    }
    
    private function buildPdfHtml(array $data, string $titre): string
    {
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #2d3748;
            line-height: 1.6;
        }
        h1 {
            color: #e53e3e;
            font-size: 24px;
            border-bottom: 3px solid #e53e3e;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        h2 {
            color: #2d3748;
            font-size: 18px;
            margin-top: 25px;
            margin-bottom: 15px;
            border-left: 4px solid #e53e3e;
            padding-left: 10px;
        }
        ul {
            list-style-type: none;
            padding-left: 0;
        }
        li {
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }
        li:before {
            content: "•";
            color: #e53e3e;
            font-weight: bold;
            position: absolute;
            left: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f7fafc;
            border-radius: 8px;
        }
        .section {
            margin-bottom: 25px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f7fafc;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{$titre}</h1>
        <p style="color: #718096;">Cahier des Charges - Généré automatiquement par SkillOra AI</p>
    </div>
    
    <div class="section">
        <h2>📝 Description</h2>
        <p>{$data['description']}</p>
    </div>
    
    <div class="section">
        <h2>🎯 Objectifs Pédagogiques</h2>
        <ul>
HTML;

        foreach ($data['objectifs'] as $obj) {
            $html .= "<li>{$obj}</li>";
        }
        
        $html .= <<<HTML
        </ul>
    </div>
    
    <div class="section">
        <h2>🎨 Fonctionnalités Requises</h2>
        <ul>
HTML;

        foreach ($data['fonctionnalites'] as $fonc) {
            $html .= "<li>{$fonc}</li>";
        }
        
        $html .= <<<HTML
        </ul>
    </div>
    
    <div class="section">
        <h2>📦 Livrables Attendus</h2>
        <ul>
HTML;

        foreach ($data['livrables'] as $liv) {
            $html .= "<li>{$liv}</li>";
        }
        
        $html .= <<<HTML
        </ul>
    </div>
    
    <div class="section">
        <h2>✅ Critères d'Évaluation</h2>
        <table>
            <thead>
                <tr>
                    <th>Critère</th>
                    <th>Pondération</th>
                </tr>
            </thead>
            <tbody>
HTML;

        foreach ($data['criteres_evaluation'] as $critere => $poids) {
            $html .= "<tr><td>{$critere}</td><td>{$poids}</td></tr>";
        }
        
        $html .= <<<HTML
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 40px; text-align: center; color: #a0aec0; font-size: 12px;">
        <p>Document généré le <?php echo date('d/m/Y à H:i'); ?> par SkillOra AI Platform</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }
}