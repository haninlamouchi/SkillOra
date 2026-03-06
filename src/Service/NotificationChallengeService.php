<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\LivrableChallenge;
use App\Entity\Participation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class NotificationChallengeService
{
    private string $senderEmail = 'skillora@example.com';

    public function __construct(
        private EntityManagerInterface $em,
        private HubInterface $hub,
        private RouterInterface $router,
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {}

    // ── Responsable reçoit : étudiant soumet un livrable ──────────
    public function notifierSoumissionLivrable(User $etudiant, User $responsable, LivrableChallenge $livrable): void
    {
        $lien = $this->router->generate('app_responsable_livrables');

        $notif = new Notification();
        $notif->setMessage(
            "📤 " . $etudiant->getPrenom() . " " . $etudiant->getNom() .
            " a soumis un livrable pour le challenge \"" . ($livrable->getChallenge()?->getTitre() ?? '') . "\""
        );
        $notif->setType('livrable_soumis');
        $notif->setExpediteur($etudiant);
        $notif->setDestinataire($responsable);
        $notif->setLienRedirection($lien);
        $notif->setRoleExpediteur($etudiant->getRole());

        $this->em->persist($notif);
        $this->em->flush();
        $this->push($notif);
    }

    // ── Responsable reçoit : groupe participe à un challenge ──────
    public function notifierParticipation(User $etudiant, User $responsable, Participation $participation): void
    {
        $lien = $this->router->generate('app_responsable_participations');

        $notif = new Notification();
        $notif->setMessage(
            "👥 Le groupe \"" . ($participation->getGroupe()?->getNomGroupe() ?? '') .
            "\" a rejoint le challenge \"" . ($participation->getChallenge()?->getTitre() ?? '') . "\""
        );
        $notif->setType('participation');
        $notif->setExpediteur($etudiant);
        $notif->setDestinataire($responsable);
        $notif->setLienRedirection($lien);
        $notif->setRoleExpediteur($etudiant->getRole());

        $this->em->persist($notif);
        $this->em->flush();
        $this->push($notif);
    }

    // ── Responsable reçoit : étudiant supprime un livrable ────────
    public function notifierSuppressionLivrable(User $etudiant, User $responsable, string $challengeTitre): void
    {
        $lien = $this->router->generate('app_responsable_livrables');

        $notif = new Notification();
        $notif->setMessage(
            "🗑️ " . $etudiant->getPrenom() . " " . $etudiant->getNom() .
            " a supprimé son livrable pour le challenge \"" . $challengeTitre . "\""
        );
        $notif->setType('livrable_supprime');
        $notif->setExpediteur($etudiant);
        $notif->setDestinataire($responsable);
        $notif->setLienRedirection($lien);
        $notif->setRoleExpediteur($etudiant->getRole());

        $this->em->persist($notif);
        $this->em->flush();
        $this->push($notif);
    }

    // ── Étudiant reçoit : livrable validé ─────────────────────────
    public function notifierLivrableValide(User $responsable, User $etudiant, LivrableChallenge $livrable): void
    {
        $lien = $this->router->generate('app_etudiant_livrables');

        $notif = new Notification();
        $notif->setMessage(
            "✅ Votre livrable pour le challenge \"" . ($livrable->getChallenge()?->getTitre() ?? '') .
            "\" a été validé par " . $responsable->getPrenom() . " " . $responsable->getNom() . " !"
        );
        $notif->setType('livrable_valide');
        $notif->setExpediteur($responsable);
        $notif->setDestinataire($etudiant);
        $notif->setLienRedirection($lien);
        $notif->setRoleExpediteur($responsable->getRole());

        $this->em->persist($notif);
        $this->em->flush();
        $this->push($notif);
    }

    // ── Étudiant reçoit : livrable refusé ─────────────────────────
    public function notifierLivrableRefuse(User $responsable, User $etudiant, LivrableChallenge $livrable): void
    {
        $lien = $this->router->generate('app_etudiant_livrables');

        $notif = new Notification();
        $notif->setMessage(
            "❌ Votre livrable pour le challenge \"" . ($livrable->getChallenge()?->getTitre() ?? '') .
            "\" a été refusé. Vous pouvez soumettre un nouveau livrable."
        );
        $notif->setType('livrable_refuse');
        $notif->setExpediteur($responsable);
        $notif->setDestinataire($etudiant);
        $notif->setLienRedirection($lien);
        $notif->setRoleExpediteur($responsable->getRole());

        $this->em->persist($notif);
        $this->em->flush();
        $this->push($notif);
    }

    // ✅ NOUVELLE : Notifier un étudiant que son livrable a été évalué (avec email)
    public function notifierEvaluationLivrable($responsable, $etudiant, string $challengeTitre, float $noteGlobale): void
    {
        // Notification Mercure
        $lien = $this->router->generate('app_etudiant_livrables');
        
        $notif = new Notification();
        $notif->setMessage(
            "📊 Votre livrable pour le challenge \"" . $challengeTitre .
            "\" a été évalué ! Note : " . $noteGlobale . "/20"
        );
        $notif->setType('livrable_evalue');
        $notif->setExpediteur($responsable);
        $notif->setDestinataire($etudiant);
        $notif->setLienRedirection($lien);
        $notif->setRoleExpediteur($responsable->getRole());

        $this->em->persist($notif);
        $this->em->flush();
        $this->push($notif);

        // Email détaillé
        try {
            $subject = '✅ Votre livrable a été évalué !';
            $mention = $this->getMention($noteGlobale);
            
            $htmlContent = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #15803d, #22c55e); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='color: white; margin: 0;'>✅ Livrable Évalué</h1>
                </div>
                
                <div style='background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px;'>
                    <p style='font-size: 16px; color: #374151;'>
                        Bonjour <strong>{$etudiant->getPrenom()}</strong>,
                    </p>
                    
                    <p style='font-size: 16px; color: #374151;'>
                        Votre livrable pour le challenge <strong style='color: #8b0000;'>{$challengeTitre}</strong> 
                        a été évalué par <strong>{$responsable->getPrenom()} {$responsable->getNom()}</strong>.
                    </p>
                    
                    <div style='background: #dcfce7; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;'>
                        <div style='font-size: 14px; color: #15803d; font-weight: 600; text-transform: uppercase;'>
                            Note Globale
                        </div>
                        <div style='font-size: 48px; font-weight: 900; color: #15803d; margin: 10px 0;'>
                            {$noteGlobale}/20
                        </div>
                        <div style='font-size: 16px; color: #15803d;'>
                            {$mention}
                        </div>
                    </div>
                    
                    <p style='font-size: 16px; color: #374151;'>
                        Connectez-vous à SkillOra pour consulter votre évaluation détaillée, 
                        vos notes par critère et les commentaires du responsable.
                    </p>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='http://localhost:8000/etudiant/mes-livrables' 
                           style='background: linear-gradient(135deg, #8b0000, #c0392b); 
                                  color: white; 
                                  padding: 15px 40px; 
                                  text-decoration: none; 
                                  border-radius: 8px; 
                                  display: inline-block;
                                  font-weight: 600;'>
                            📊 Voir mon évaluation
                        </a>
                    </div>
                    
                    <p style='font-size: 14px; color: #9ca3af; margin-top: 30px; text-align: center;'>
                        Cet email est envoyé automatiquement par SkillOra.
                    </p>
                </div>
            </div>
            ";

            $email = (new Email())
                ->from($this->senderEmail)
                ->to($etudiant->getEmail())
                ->subject($subject)
                ->html($htmlContent);

            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error('Erreur notification évaluation: ' . $e->getMessage());
        }
    }

    // ✅ NOUVELLE : Notifier un étudiant que son livrable a été refusé (avec email)
    public function notifierRefusLivrable($responsable, $etudiant, string $challengeTitre): void
    {
        // Notification Mercure
        $lien = $this->router->generate('app_etudiant_livrables');
        
        $notif = new Notification();
        $notif->setMessage(
            "❌ Votre livrable pour le challenge \"" . $challengeTitre .
            "\" a été refusé. Consultez les commentaires."
        );
        $notif->setType('livrable_refuse');
        $notif->setExpediteur($responsable);
        $notif->setDestinataire($etudiant);
        $notif->setLienRedirection($lien);
        $notif->setRoleExpediteur($responsable->getRole());

        $this->em->persist($notif);
        $this->em->flush();
        $this->push($notif);

        // Email détaillé
        try {
            $subject = '❌ Votre livrable a été refusé';
            
            $htmlContent = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #dc2626, #991b1b); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='color: white; margin: 0;'>❌ Livrable Refusé</h1>
                </div>
                
                <div style='background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px;'>
                    <p style='font-size: 16px; color: #374151;'>
                        Bonjour <strong>{$etudiant->getPrenom()}</strong>,
                    </p>
                    
                    <p style='font-size: 16px; color: #374151;'>
                        Votre livrable pour le challenge <strong style='color: #8b0000;'>{$challengeTitre}</strong> 
                        a été refusé par <strong>{$responsable->getPrenom()} {$responsable->getNom()}</strong>.
                    </p>
                    
                    <div style='background: #fee2e2; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc2626;'>
                        <p style='margin: 0; color: #991b1b; font-weight: 600;'>
                            ⚠️ Action requise : Vous devez soumettre un nouveau livrable corrigé.
                        </p>
                    </div>
                    
                    <p style='font-size: 16px; color: #374151;'>
                        Consultez les commentaires du responsable pour comprendre les points à améliorer 
                        et soumettez une nouvelle version de votre travail.
                    </p>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='http://localhost:8000/etudiant/mes-livrables' 
                           style='background: linear-gradient(135deg, #8b0000, #c0392b); 
                                  color: white; 
                                  padding: 15px 40px; 
                                  text-decoration: none; 
                                  border-radius: 8px; 
                                  display: inline-block;
                                  font-weight: 600;'>
                            📝 Voir les commentaires
                        </a>
                    </div>
                    
                    <p style='font-size: 14px; color: #9ca3af; margin-top: 30px; text-align: center;'>
                        Cet email est envoyé automatiquement par SkillOra.
                    </p>
                </div>
            </div>
            ";

            $email = (new Email())
                ->from($this->senderEmail)
                ->to($etudiant->getEmail())
                ->subject($subject)
                ->html($htmlContent);

            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error('Erreur notification refus: ' . $e->getMessage());
        }
    }

    // ✅ Helper pour obtenir la mention selon la note
    private function getMention(float $note): string
    {
        if ($note >= 16) return '⭐⭐⭐⭐⭐ Excellent !';
        if ($note >= 14) return '⭐⭐⭐⭐ Très Bien !';
        if ($note >= 12) return '⭐⭐⭐ Bien';
        if ($note >= 10) return '⭐⭐ Passable';
        return '⭐ À améliorer';
    }

    // ── Push Mercure ───────────────────────────────────────────────
    private function push(Notification $notif): void
    {
        try {
            $createdAt = $notif->getCreatedAt();
            $update = new Update(
                "/notifications/user/" . ($notif->getDestinataire()?->getId() ?? 0),
                json_encode([
                    'id'             => $notif->getId(),
                    'message'        => $notif->getMessage(),
                    'type'           => $notif->getType(),
                    'createdAt'      => $createdAt ? $createdAt->format('d/m/Y H:i') : '',
                    'lien'           => $notif->getLienRedirection(),
                    'roleExpediteur' => $notif->getRoleExpediteur(),
                    'pubId'          => null,
                ]) ?: '{}'
            );
            $this->hub->publish($update);
        } catch (\Throwable $e) {
            // Mercure non disponible — la notif est déjà en BDD, pas grave
        }
    }
}