<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;

class NotificationServicech
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    public function envoyerEmail(string $to, string $sujet, string $html): bool
    {
        try {
            $email = (new Email())
                ->from(new Address('kmaryem50@gmail.com', 'SkillOra Platform'))
                ->to($to)
                ->subject($sujet)
                ->html($html);  // ← HTML au lieu de text

            $this->mailer->send($email);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email: ' . $e->getMessage());
            return false;
        }
    }

    public function envoyerEmailNouveauChallenge(string $to, string $nomEtudiant, string $titreChallenge, string $dateDebut, string $dateFin): bool
    {
        $sujet = "🏆 Nouveau Challenge disponible : " . $titreChallenge;

        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Nouveau Challenge — SkillOra</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{background:#f1f1f1;font-family:\'Segoe UI\',Arial,sans-serif}
  .wrap{max-width:620px;margin:30px auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 6px 30px rgba(0,0,0,.12)}
  .hdr{background:linear-gradient(135deg,#5a0000 0%,#8b1a1a 60%,#a52a2a 100%);padding:28px 36px}
  .hdr-logo{font-size:1.9rem;font-weight:800;color:#fff;letter-spacing:2px}
  .hdr-logo span{color:#ff8080}
  .hdr-sub{color:rgba(255,255,255,.7);font-size:.82rem;margin-top:4px}
  .strip{padding:12px 20px;font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:10px;background:#fff8e1;color:#856404;border-left:5px solid #ffc107}
  .body{padding:28px 36px}
  .intro{font-size:.93rem;color:#555;line-height:1.7;margin-bottom:20px}
  .intro strong{color:#8b0000}
  .challenge-card{border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:20px}
  .challenge-card-head{background:linear-gradient(135deg,#5a0000,#8b1a1a);padding:14px 22px}
  .challenge-card-head h2{color:#fff;font-size:.95rem;font-weight:700;margin:0}
  .challenge-card-body{padding:18px 22px;background:#fafafa}
  table.info{width:100%;border-collapse:collapse}
  table.info td{padding:10px 6px;font-size:.84rem;border-bottom:1px solid #f0f0f0;vertical-align:top}
  table.info tr:last-child td{border-bottom:none}
  table.info .lbl{color:#999;font-weight:700;text-transform:uppercase;font-size:.72rem;letter-spacing:.5px;width:120px}
  table.info .val{color:#333;font-weight:600}
  .badge-new{display:inline-block;padding:4px 12px;border-radius:20px;font-size:.73rem;font-weight:700;background:#dcfce7;color:#15803d}
  .cta{text-align:center;margin:22px 0 6px}
  .cta a{display:inline-block;background:linear-gradient(135deg,#5a0000,#a52a2a);color:#fff;text-decoration:none;padding:13px 32px;border-radius:8px;font-weight:700;font-size:.9rem}
  .note{font-size:.75rem;color:#bbb;text-align:center;margin-top:16px}
  .ftr{background:#111;padding:16px 36px;text-align:center}
  .ftr p{color:rgba(255,255,255,.4);font-size:.72rem;margin:3px 0}
  .ftr strong{color:rgba(255,255,255,.7)}
</style>
</head>
<body>
<div class="wrap">

  <div class="hdr">
    <div class="hdr-logo">Skill<span>Ora</span></div>
    <div class="hdr-sub">Plateforme Collaborative — Challenges</div>
  </div>

  <div class="strip">
    🏆 Nouveau challenge disponible sur SkillOra !
  </div>

  <div class="body">
    <p class="intro">
      Bonjour <strong>' . htmlspecialchars($nomEtudiant) . '</strong>,<br>
      Un nouveau challenge vient d\'être créé sur SkillOra. Rejoignez-le avec votre groupe dès maintenant !
    </p>

    <div class="challenge-card">
      <div class="challenge-card-head">
        <h2>🏆 Détails du Challenge</h2>
      </div>
      <div class="challenge-card-body">
        <table class="info">
          <tr>
            <td class="lbl">Challenge</td>
            <td class="val">' . htmlspecialchars($titreChallenge) . ' &nbsp;<span class="badge-new">Nouveau</span></td>
          </tr>
          <tr>
            <td class="lbl">Date début</td>
            <td class="val">📅 ' . htmlspecialchars($dateDebut) . '</td>
          </tr>
          <tr>
            <td class="lbl">Date fin</td>
            <td class="val">⏳ ' . htmlspecialchars($dateFin) . '</td>
          </tr>
        </table>
      </div>
    </div>

    <div class="cta">
      <a href="http://localhost:8000/etudiant/challenges">🚀 Voir le Challenge</a>
    </div>
    <p class="note">Email automatique — Ne pas répondre directement.</p>
  </div>

  <div class="ftr">
    <p><strong>SkillOra Platform</strong></p>
    <p>© ' . date('Y') . ' SkillOra — Esprit, Tunis</p>
  </div>

</div>
</body>
</html>';

        return $this->envoyerEmail($to, $sujet, $html);
    }
}