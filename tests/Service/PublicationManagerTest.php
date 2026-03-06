<?php

namespace App\Tests\Service;

use App\Entity\Publication;
use App\Entity\User;
use App\Enum\StatusPublication;
use App\Enum\TypeContenu;
use App\Service\PublicationManager;
use PHPUnit\Framework\TestCase;

/**
 * ═══════════════════════════════════════════════════════════
 *  Tests unitaires — PublicationManager
 * ═══════════════════════════════════════════════════════════
 *
 *  Règles métier testées :
 *   ✅ Titre obligatoire
 *   ✅ Titre min 3 chars / max 200 chars
 *   ✅ Contenu obligatoire
 *   ✅ Contenu minimum 10 chars
 *   ✅ Statut valide
 *
 *  Exécution : php bin/phpunit tests/Service/PublicationManagerTest.php
 */
class PublicationManagerTest extends TestCase
{
    private PublicationManager $manager;

    // ── setUp : exécuté avant chaque test ────────────────────
    protected function setUp(): void
    {
        $this->manager = new PublicationManager();
    }

    // ════════════════════════════════════════════
    //  Helpers — création d'objets de test
    // ════════════════════════════════════════════

    private function makePublication(
        string  $titre   = 'Titre de test valide',
        string  $contenu = 'Contenu suffisamment long pour le test.',
        ?StatusPublication $status = null,
        ?TypeContenu $type = null
    ): Publication {
        $user = new User();

        $pub = new Publication();
        $pub->setTitre($titre);
        $pub->setContenu($contenu);
        $pub->setStatus($status ?? StatusPublication::EN_ATTENTE);
        $pub->setTypecontenu($type ?? TypeContenu::TEXTE);
        $pub->setUser($user);

        return $pub;
    }

    // ════════════════════════════════════════════
    //  ✅ TEST 1 — Publication valide
    // ════════════════════════════════════════════

    public function testPublicationValide(): void
    {
        $pub = $this->makePublication(
            'Mon premier Hackathon Symfony',
            'Voici le contenu détaillé de ma publication de test.'
        );

        $this->assertTrue($this->manager->validate($pub));
    }

    // ════════════════════════════════════════════
    //  ❌ TEST 2 — Titre vide
    // ════════════════════════════════════════════

    public function testTitreVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire.');

        $pub = $this->makePublication('   '); // espaces seulement = vide
        $this->manager->validate($pub);
    }

    // ════════════════════════════════════════════
    //  ❌ TEST 3 — Titre trop court (< 3 chars)
    // ════════════════════════════════════════════

    public function testTitreTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre doit contenir au moins 3 caractères.');

        $pub = $this->makePublication('AB'); // 2 chars — invalide
        $this->manager->validate($pub);
    }

    // ════════════════════════════════════════════
    //  ❌ TEST 4 — Titre trop long (> 200 chars)
    // ════════════════════════════════════════════

    public function testTitreTropLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre ne peut pas dépasser 200 caractères.');

        $pub = $this->makePublication(str_repeat('A', 201)); // 201 chars — invalide
        $this->manager->validate($pub);
    }

    // ════════════════════════════════════════════
    //  ✅ TEST 5 — Titre exactement 3 chars (limite basse OK)
    // ════════════════════════════════════════════

    public function testTitreLimiteBasse(): void
    {
        $pub = $this->makePublication('ABC'); // 3 chars — valide
        $this->assertTrue($this->manager->validate($pub));
    }

    // ════════════════════════════════════════════
    //  ✅ TEST 6 — Titre exactement 200 chars (limite haute OK)
    // ════════════════════════════════════════════

    public function testTitreLimiteHaute(): void
    {
        $pub = $this->makePublication(str_repeat('A', 200)); // 200 chars — valide
        $this->assertTrue($this->manager->validate($pub));
    }

    // ════════════════════════════════════════════
    //  ❌ TEST 7 — Contenu vide
    // ════════════════════════════════════════════

    public function testContenuVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le contenu est obligatoire.');

        $pub = $this->makePublication('Titre valide', '');
        $this->manager->validate($pub);
    }

    // ════════════════════════════════════════════
    //  ❌ TEST 8 — Contenu trop court (< 10 chars)
    // ════════════════════════════════════════════

    public function testContenuTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le contenu doit contenir au moins 10 caractères.');

        $pub = $this->makePublication('Titre valide', 'Court'); // 5 chars — invalide
        $this->manager->validate($pub);
    }

    // ════════════════════════════════════════════
    //  ✅ TEST 9 — Contenu exactement 10 chars (limite OK)
    // ════════════════════════════════════════════

    public function testContenuLimiteMinimum(): void
    {
        $pub = $this->makePublication('Titre valide', '1234567890'); // 10 chars — valide
        $this->assertTrue($this->manager->validate($pub));
    }

    // ════════════════════════════════════════════
    //  ❌ TEST 10 — Statut null
    // ════════════════════════════════════════════

    public function testStatutNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut est obligatoire.');

        $pub = new Publication();
        $pub->setTitre('Titre valide ici');
        $pub->setContenu('Contenu valide pour le test.');

        // Le constructeur de Publication force EN_ATTENTE automatiquement
        // → on utilise ReflectionClass pour forcer la propriété à null
        $reflection = new \ReflectionClass($pub);
        $prop = $reflection->getProperty('status');
        $prop->setAccessible(true);
        $prop->setValue($pub, null);

        $this->manager->validate($pub);
    }

    // ════════════════════════════════════════════
    //  ✅ TEST 11 — Statut EN_ATTENTE valide
    // ════════════════════════════════════════════

    public function testStatutEnAttente(): void
    {
        $pub = $this->makePublication(
            'Titre valide',
            'Contenu valide pour le test.',
            StatusPublication::EN_ATTENTE
        );
        $this->assertTrue($this->manager->validate($pub));
    }

    // ════════════════════════════════════════════
    //  ✅ TEST 12 — Statut PUBLIE valide
    // ════════════════════════════════════════════

    public function testStatutPublie(): void
    {
        $pub = $this->makePublication(
            'Titre valide',
            'Contenu valide pour le test.',
            StatusPublication::PUBLIE
        );
        $this->assertTrue($this->manager->validate($pub));
    }
}