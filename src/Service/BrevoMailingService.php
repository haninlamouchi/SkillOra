<?php

namespace App\Service;

use App\Entity\Commentaire;
use App\Entity\Publication;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

class BrevoMailingService
{
    private const ADMIN_EMAIL = 'haninlamouchi42@gmail.com';
    private const ADMIN_NAME  = 'Admin SkillOra';
    private const FROM_EMAIL  = 'haninlamouchi42@gmail.com'; // ← doit être vérifié sur Brevo
    private const FROM_NAME   = 'SkillOra Platform';
    private const BREVO_URL   = 'https://api.brevo.com/v3/smtp/email';

    public function __construct(
        private HttpClientInterface $httpClient,
        private Environment $twig,
        private string $brevoApiKey
    ) {}

    // ══════════════════════════════════════════════════
    //  PUBLICATIONS
    // ══════════════════════════════════════════════════

    /**
     * Publication étudiant validée par responsable → PUBLIÉ → email admin
     * Appelé dans : ResponsableClubPublicationController::valider()
     */
    public function notifierNouvellePublicationEtudiant(Publication $publication): void
    {
        $this->envoyerEmailPublication($publication, 'Étudiant');
    }

    /**
     * Responsable publie directement → PUBLIÉ → email admin
     * Appelé dans : ResponsableClubPublicationController::new()
     */
    public function notifierNouvellePublicationResponsable(Publication $publication): void
    {
        $this->envoyerEmailPublication($publication, 'Responsable Club');
    }

    // ══════════════════════════════════════════════════
    //  COMMENTAIRES
    // ══════════════════════════════════════════════════

    /**
     * Commentaire posté → email admin
     * Appelé dans : ForumController::show()
     */
    public function notifierNouveauCommentaire(Commentaire $commentaire): void
    {
        $auteur      = $commentaire->getUser();
        $publication = $commentaire->getPublication();
        $roleLabel   = $auteur->getRole() === 'responsable_club' ? 'Responsable Club' : 'Étudiant';

        $htmlContent = $this->twig->render('emails/admin_nouveau_commentaire.html.twig', [
            'commentaire'  => $commentaire,
            'publication'  => $publication,
            'auteur'       => $auteur,
            'roleLabel'    => $roleLabel,
            'dateFormatee' => $commentaire->getDateCommentaire()->format('d/m/Y à H:i'),
            'apercu'       => mb_strlen($commentaire->getContenu()) > 200
                                ? mb_substr($commentaire->getContenu(), 0, 200) . '…'
                                : $commentaire->getContenu(),
        ]);

        $payload = [
            'sender'      => ['name' => self::FROM_NAME,  'email' => self::FROM_EMAIL],
            'to'          => [['name' => self::ADMIN_NAME, 'email' => self::ADMIN_EMAIL]],
            'subject'     => '💬 Nouveau commentaire — ' . $publication->getTitre(),
            'htmlContent' => $htmlContent,
            'textContent' => sprintf(
                "Nouveau commentaire\n\nAuteur : %s %s (%s)\nPublication : %s\nDate : %s\n\nContenu :\n%s\n\nGérer : http://localhost:8000/admin/forum/commentaires",
                $auteur->getPrenom(), $auteur->getNom(), $roleLabel,
                $publication->getTitre(),
                $commentaire->getDateCommentaire()->format('d/m/Y à H:i'),
                $commentaire->getContenu()
            ),
            'tags' => ['skillora', 'forum', 'commentaire'],
        ];

        $this->envoyer($payload);
    }

    // ══════════════════════════════════════════════════
    //  PRIVÉ
    // ══════════════════════════════════════════════════

    private function envoyerEmailPublication(Publication $publication, string $roleLabel): void
    {
        $auteur    = $publication->getUser();
        $isAttente = $publication->getStatus()->value === 'EN_ATTENTE';
        $apercu    = mb_strlen($publication->getContenu()) > 250
                       ? mb_substr($publication->getContenu(), 0, 250) . '…'
                       : $publication->getContenu();

        // ── Encoder l'image en base64 pour qu'elle s'affiche dans Gmail ──
        $imageBase64 = null;
        $imageMime   = null;
        if ($publication->getFichier()) {
            $filePath = __DIR__ . '/../../public/uploads/publications/' . $publication->getFichier();
            if (file_exists($filePath)) {
                $ext     = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $mimeMap = [
                    'jpg'  => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png'  => 'image/png',
                    'gif'  => 'image/gif',
                    'webp' => 'image/webp',
                ];
                if (isset($mimeMap[$ext])) {
                    $imageBase64 = base64_encode(file_get_contents($filePath));
                    $imageMime   = $mimeMap[$ext];
                }
            }
        }

        $htmlContent = $this->twig->render('emails/admin_nouvelle_publication.html.twig', [
            'publication'  => $publication,
            'auteur'       => $auteur,
            'roleLabel'    => $roleLabel,
            'dateFormatee' => $publication->getDatePublication()->format('d/m/Y à H:i'),
            'typeLabel'    => ucfirst($publication->getTypecontenu()->value),
            'statutLabel'  => $isAttente ? 'En attente' : 'Publié',
            'apercu'       => $apercu,
            'imageBase64'  => $imageBase64,
            'imageMime'    => $imageMime,
        ]);

        $payload = [
            'sender'      => ['name' => self::FROM_NAME,  'email' => self::FROM_EMAIL],
            'to'          => [['name' => self::ADMIN_NAME, 'email' => self::ADMIN_EMAIL]],
            'subject'     => '📢 Publication validée sur SkillOra — ' . $publication->getTitre(),
            'htmlContent' => $htmlContent,
            'textContent' => sprintf(
                "Publication validée sur SkillOra\n\nAuteur : %s %s (%s)\nTitre : %s\nType : %s\nStatut : Publié\nDate : %s\n\nAperçu :\n%s\n\nGérer : http://localhost:8000/admin/forum/publications",
                $auteur->getPrenom(), $auteur->getNom(), $roleLabel,
                $publication->getTitre(),
                ucfirst($publication->getTypecontenu()->value),
                $publication->getDatePublication()->format('d/m/Y à H:i'),
                $apercu
            ),
            'tags' => ['skillora', 'forum', 'publication'],
        ];

        $this->envoyer($payload);
    }

    /**
     * Appel HTTP POST → API Brevo
     */
    private function envoyer(array $payload): void
    {
        try {
            $response = $this->httpClient->request('POST', self::BREVO_URL, [
                'headers' => [
                    'accept'       => 'application/json',
                    'content-type' => 'application/json',
                    'api-key'      => $this->brevoApiKey,
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $body       = $response->getContent(false);

            if ($statusCode === 201) {
                error_log('[BrevoMailingService] ✅ Email envoyé avec succès');
            } else {
                error_log('[BrevoMailingService] ❌ Erreur ' . $statusCode . ' : ' . $body);
            }

        } catch (\Exception $e) {
            error_log('[BrevoMailingService] Exception : ' . $e->getMessage());
        }
    }
}