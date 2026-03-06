<?php

namespace App\Controller;

use App\Entity\ParticipationFormation;
use App\Repository\FormationRepository;
use App\Repository\ParticipationFormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentController extends AbstractController
{
    public function __construct(
        private FormationRepository $formationRepo,
        private ParticipationFormationRepository $participationRepo,
        private EntityManagerInterface $em,
    ) {}

    // ──────────────────────────────────────────────
    //  Helper: configure Stripe API key
    // ──────────────────────────────────────────────
    private function initStripe(): void
    {
        $stripeKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
        if (!$stripeKey) {
            throw new \RuntimeException('STRIPE_SECRET_KEY is not configured.');
        }
        Stripe::setApiKey($stripeKey);
    }

    // ──────────────────────────────────────────────
    //  GET /pay  →  create Stripe Checkout session
    // ──────────────────────────────────────────────
    #[Route('/pay', name: 'app_pay')]
    public function pay(Request $request): Response
    {
        $this->initStripe();

        /** @var \App\Entity\User $user */
        $user        = $this->getUser();
        $formationId = $request->query->getInt('formationId');
        $formationObj = $formationId ? $this->formationRepo->find($formationId) : null;
        $formation   = $formationObj instanceof \App\Entity\Formation ? $formationObj : null;

        $name       = $formation && $formation->getTitre() ? $formation->getTitre() : 'Formation';
        $priceEur   = $formation && $formation->getPrix() ? $formation->getPrix() * 0.30 : 10.00;
        $unitAmount = (int) round($priceEur * 100); // cents

        $userEmail  = $user ? ($user->getEmail() ?? '') : '';
        $userName   = $user ? trim($user->getPrenom() . ' ' . $user->getPrenom()) : '';

        // Save pending-payment context in session for the cancel route
        $request->getSession()->set('payment_pending', [
            'formationId'   => $formationId,
            'formationName' => $name,
            'userEmail'     => $userEmail,
            'userName'      => $userName,
            'unitAmount'    => $unitAmount,
        ]);

        $successUrl = $this->generateUrl(
            'payment_success',
            ['session_id' => '{CHECKOUT_SESSION_ID}'],   // Stripe fills this in
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $cancelUrl = $formation
            ? $this->generateUrl('front_formation_show', ['id' => $formationId], UrlGeneratorInterface::ABSOLUTE_URL)
            : $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $stripeSession = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'eur',
                    'product_data' => ['name' => $name], // $name is always string now
                    'unit_amount'  => $unitAmount,
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
            'metadata' => [
                'formationId'   => (string) $formationId,
                'formationName' => $name,
                'userEmail'     => $userEmail,
                'userName'      => $userName,
                'unitAmount'    => (string) $unitAmount,
            ],
        ]);

        return $this->redirect($stripeSession->url ?? '/');
    }

    // ──────────────────────────────────────────────
    //  GET /success  →  payment confirmed by Stripe
    // ──────────────────────────────────────────────
    #[Route('/success', name: 'payment_success')]
    public function success(Request $request, MailerInterface $mailer): Response
    {
        $this->initStripe();

        $sessionId = $request->query->get('session_id', '');
        $meta      = [];

        if ($sessionId) {
            try {
                $stripeSession = StripeSession::retrieve($sessionId);
                $meta = $stripeSession->metadata?->toArray() ?? [];
            } catch (\Throwable) {
                // Stripe error — proceed without metadata
            }
        }

        $currentUser   = $this->getUser();
        $userEmail     = $meta['userEmail']     ?? ($currentUser instanceof \App\Entity\User ? ($currentUser->getEmail() ?? '') : '');
        $userName      = $meta['userName']      ?? ($currentUser instanceof \App\Entity\User ? trim($currentUser->getPrenom() . ' ' . $currentUser->getNom()) : '');
        $formationName = $meta['formationName'] ?? 'votre formation';
        $unitAmount    = isset($meta['unitAmount']) ? (int) $meta['unitAmount'] : 0;
        $amountEur     = number_format($unitAmount / 100, 2, ',', ' ');

        // ── Register participation as paid ──
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $formationId = isset($meta['formationId']) ? (int) $meta['formationId'] : 0;
        if ($user && $formationId) {
            $formationObj = $this->formationRepo->find($formationId);
            $formation = $formationObj instanceof \App\Entity\Formation ? $formationObj : null;
            if ($formation && !$this->participationRepo->hasPaidParticipation($user, $formation)) {
                $participation = new ParticipationFormation();
                $participation->setUser($user);
                $participation->setFormation($formation);
                $participation->setPaymentStatus(true);
                $this->em->persist($participation);
                $this->em->flush();
            }
        }

        if ($userEmail) {
            $html = $this->renderSuccessEmailHtml($userName, $formationName, $amountEur);
            $email = (new Email())
                ->from('noreply@skillora.com')
                ->to($userEmail)
                ->subject('✅ Confirmation de paiement — ' . $formationName)
                ->html($html);
            try { $mailer->send($email); } catch (\Throwable) {}
        }

        return $this->render('frontoffice/payment/success.html.twig', [
            'formationName' => $formationName,
            'userName'      => $userName,
            'amountEur'     => $amountEur,
        ]);
    }

    // ──────────────────────────────────────────────
    //  GET /cancel  →  user cancelled checkout
    // ──────────────────────────────────────────────
    #[Route('/cancel', name: 'payment_cancel')]
    public function cancel(Request $request, MailerInterface $mailer): Response
    {
        $pending = $request->getSession()->get('payment_pending', []);
        $request->getSession()->remove('payment_pending');

        $cancelUser    = $this->getUser();
        $userEmail     = $pending['userEmail']     ?? ($cancelUser instanceof \App\Entity\User ? ($cancelUser->getEmail() ?? '') : '');
        $userName      = $pending['userName']      ?? ($cancelUser instanceof \App\Entity\User ? trim($cancelUser->getPrenom() . ' ' . $cancelUser->getNom()) : '');
        $formationName = $pending['formationName'] ?? 'votre formation';
        $unitAmount    = $pending['unitAmount']    ?? 0;
        $amountEur     = number_format($unitAmount / 100, 2, ',', ' ');

        if ($userEmail) {
            $html = $this->renderCancelEmailHtml($userName, $formationName, $amountEur);
            $email = (new Email())
                ->from('noreply@skillora.com')
                ->to($userEmail)
                ->subject('❌ Paiement annulé — ' . $formationName)
                ->html($html);
            try { $mailer->send($email); } catch (\Throwable) {}
        }

        return $this->render('frontoffice/payment/cancel.html.twig', [
            'formationName' => $formationName,
            'userName'      => $userName,
        ]);
    }

    // ──────────────────────────────────────────────
    //  Email HTML helpers
    // ──────────────────────────────────────────────
    private function renderSuccessEmailHtml(string $name, string $formationName, string $amount): string
    {
        return '
<div style="font-family:DM Sans,sans-serif;max-width:520px;margin:0 auto;padding:32px 24px;background:#fff;border-radius:16px;border:1px solid #e5e7eb;">
  <div style="text-align:center;font-size:3rem;margin-bottom:8px;">✅</div>
  <h2 style="color:#16a34a;font-size:1.4rem;text-align:center;margin:0 0 16px;">Paiement confirmé !</h2>
  <p style="color:#374151;font-size:0.95rem;line-height:1.6;margin:0 0 16px;">
    Bonjour <strong>' . htmlspecialchars($name) . '</strong>,<br>
    Votre paiement pour la formation <strong>' . htmlspecialchars($formationName) . '</strong> a bien été traité.
  </p>
  <div style="background:#f0fdf4;border:2px solid #16a34a;border-radius:12px;padding:16px 20px;margin-bottom:24px;">
    <table style="width:100%;font-size:0.9rem;color:#374151;">
      <tr><td>Formation</td><td style="text-align:right;font-weight:700;">' . htmlspecialchars($formationName) . '</td></tr>
      <tr><td>Montant payé</td><td style="text-align:right;font-weight:700;">' . $amount . ' €</td></tr>
      <tr><td>Statut</td><td style="text-align:right;color:#16a34a;font-weight:700;">Payé ✓</td></tr>
    </table>
  </div>
  <p style="color:#6b7280;font-size:0.8rem;margin:0;">Vous pouvez maintenant accéder à votre formation sur SkillOra. Merci pour votre confiance !</p>
</div>';
    }

    private function renderCancelEmailHtml(string $name, string $formationName, string $amount): string
    {
        return '
<div style="font-family:DM Sans,sans-serif;max-width:520px;margin:0 auto;padding:32px 24px;background:#fff;border-radius:16px;border:1px solid #e5e7eb;">
  <div style="text-align:center;font-size:3rem;margin-bottom:8px;">❌</div>
  <h2 style="color:#dc2626;font-size:1.4rem;text-align:center;margin:0 0 16px;">Paiement annulé</h2>
  <p style="color:#374151;font-size:0.95rem;line-height:1.6;margin:0 0 16px;">
    Bonjour <strong>' . htmlspecialchars($name) . '</strong>,<br>
    Votre tentative de paiement pour la formation <strong>' . htmlspecialchars($formationName) . '</strong> a été annulée.
    Aucun montant n\'a été débité.
  </p>
  <div style="background:#fef2f2;border:2px solid #dc2626;border-radius:12px;padding:16px 20px;margin-bottom:24px;">
    <table style="width:100%;font-size:0.9rem;color:#374151;">
      <tr><td>Formation</td><td style="text-align:right;font-weight:700;">' . htmlspecialchars($formationName) . '</td></tr>
      <tr><td>Montant</td><td style="text-align:right;font-weight:700;">' . $amount . ' €</td></tr>
      <tr><td>Statut</td><td style="text-align:right;color:#dc2626;font-weight:700;">Annulé ✗</td></tr>
    </table>
  </div>
  <p style="color:#6b7280;font-size:0.8rem;margin:0;">Si ce n\'était pas vous ou si vous souhaitez réessayer, rendez-vous sur SkillOra.</p>
</div>';
    }
}

