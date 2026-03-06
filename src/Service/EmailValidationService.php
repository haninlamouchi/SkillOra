<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class EmailValidationService
{
    // Domaines connus et valides (whitelist express)
    private const KNOWN_VALID_DOMAINS = [
        'gmail.com', 'yahoo.com', 'yahoo.fr', 'hotmail.com', 'hotmail.fr',
        'outlook.com', 'outlook.fr', 'live.com', 'live.fr', 'icloud.com',
        'me.com', 'mac.com', 'protonmail.com', 'proton.me', 'tutanota.com',
        'gmx.com', 'gmx.fr', 'orange.fr', 'sfr.fr', 'free.fr', 'laposte.net',
        'wanadoo.fr', 'bbox.fr', 'esprit.tn', 'esprit.com',
    ];

    // Domaines jetables connus (blacklist)
    private const DISPOSABLE_DOMAINS = [
        'mailinator.com', 'guerrillamail.com', 'tempmail.com', 'throwaway.email',
        'yopmail.com', 'trashmail.com', 'sharklasers.com', 'guerrillamailblock.com',
        'grr.la', 'guerrillamail.info', 'guerrillamail.biz', 'guerrillamail.de',
        'guerrillamail.net', 'guerrillamail.org', 'spam4.me', 'dispostable.com',
        'maildrop.cc', 'discard.email', 'fakeinbox.com', 'spamgourmet.com',
        'mytrashmail.com', 'mailnull.com', 'spamhereplease.com', 'trashmail.me',
        'trashmail.net', 'trashmail.org', 'throwam.com', 'tempr.email',
        'get2mail.fr', 'jetable.fr.nf', 'mail-temporaire.fr', 'poubelle.live',
    ];

    public function __construct(private HttpClientInterface $httpClient) {}

    public function validate(string $email): array
    {
        $email = strtolower(trim($email));

        // ── 1. FORMAT PHP ──────────────────────────────────────────────
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->reject('Invalid email format.');
        }

        // ── 2. EXTRACTION DOMAINE ──────────────────────────────────────
        $domain = substr(strrchr($email, '@') ?: '', 1);

        if (empty($domain) || !str_contains($domain, '.')) {
            return $this->reject('Invalid email domain.');
        }

        // ── 3. DOMAINE JETABLE (blacklist locale) ──────────────────────
        if (in_array($domain, self::DISPOSABLE_DOMAINS, true)) {
            return $this->reject('Disposable emails are not allowed.', disposable: true);
        }

        // ── 4. WHITELIST — domaines connus → accepté directement ───────
        if (in_array($domain, self::KNOWN_VALID_DOMAINS, true)) {
            return $this->accept();
        }

        // ── 5. VÉRIFICATION DNS LOCALE ─────────────────────────────────
        $hasMx = checkdnsrr($domain, 'MX');
        $hasA  = !$hasMx && checkdnsrr($domain, 'A');

        if (!$hasMx && !$hasA) {
            return $this->reject('This email domain does not exist.');
        }

        // ── 6. APPEL DISIFY (enrichissement, pas décision principale) ──
        try {
            $response = $this->httpClient->request(
                'GET',
                'https://www.disify.com/api/email/' . urlencode($email),
                ['timeout' => 3]
            );

            $data = $response->toArray(false);

            // Disify dit disposable → rejeter
            if ($data['disposable'] ?? false) {
                return $this->reject('Disposable emails are not allowed.', disposable: true);
            }

            // Disify dit dns=false MAIS notre DNS local dit OK → on fait confiance au DNS local
            // Disify dit dns=false ET notre DNS local dit KO → rejeter
            if (!($data['dns'] ?? true) && !$hasMx && !$hasA) {
                return $this->reject('This email domain does not exist.');
            }

            // Disify dit format=false MAIS filter_var dit OK → on ignore Disify
            // (Disify a des faux positifs sur certains formats valides RFC)

        } catch (\Exception) {
            // Disify inaccessible → on se base sur DNS local uniquement (déjà validé étape 5)
        }

        // ── 7. TOUT EST OK ─────────────────────────────────────────────
        return $this->accept();
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function reject(
        string $message,
        bool $disposable = false,
        bool $dns = false
    ): array {
        return [
            'isValid'      => false,
            'isDisposable' => $disposable,
            'isDns'        => $dns,
            'message'      => $message,
        ];
    }

    private function accept(): array
    {
        return [
            'isValid'      => true,
            'isDisposable' => false,
            'isDns'        => true,
            'message'      => '',
        ];
    }
}