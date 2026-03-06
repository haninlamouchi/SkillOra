<?php

namespace App\Tests\Service;

use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Entity\User;
use App\Service\CommentaireManager;
use PHPUnit\Framework\TestCase;

/**
 * ═══════════════════════════════════════════════════════════
 *  Tests unitaires — CommentaireManager
 * ═══════════════════════════════════════════════════════════
 *
 *  Règles métier testées :
 *   ✅ Contenu obligatoire
 *   ✅ Contenu min 5 chars / max 500 chars
 *   ✅ Publication obligatoire
 *   ✅ User (auteur) obligatoire
 *
 *  Exécution : php bin/phpunit tests/Service/CommentaireManagerTest.php
 */
class CommentaireManagerTest extends TestCase
{
    private CommentaireManager $manager;

    protected function setUp(): void
    {
        $this->manager = new CommentaireManager();
    }

    // ════════════════════════════════════════════
    //  Helper — créer un commentaire valide
    // ════════════════════════════════════════════

    private function makeCommentaire(
        string       $contenu     = 'Super publication, merci !',
        bool         $avecUser    = true,
        bool         $avecPub     = true
    ): Commentaire {
        $commentaire = new Commentaire();
        $commentaire->setContenu($contenu);

        if ($avecUser) {
            $commentaire->setUser(new User());
        }

        if ($avecPub) {
            $commentaire->setPublication(new Publication());
        }

        return $commentaire;
    }

    // ════════════════════════════════════════════
    //  ✅ TEST 1 — Commentaire valide
    // ════════════════════════════════════════════

    public function testCommentaireValide(): void
    {
        $commentaire = $this->makeCommentaire('Super publication, merci !');
        $this->assertTrue($this->manager->validate($commentaire));
    }

    // ════════════════════════════════════════════
    //  ❌ TEST 2 — Contenu vide
    // ════════════════════════════════════════════

    public function testContenuVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le contenu du commentaire est obligatoire.');

        $commentaire = $this->makeCommentaire('');
        $this->manager->validate($commentaire);
    }

    // ════════════════════════════════════════════
    //  ❌ TEST 3 — Contenu trop court (< 5 chars)
    // ════════════════════════════════════════════

    public function testContenuTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le commentaire doit contenir au moins 5 caractères.');

        $commentaire = $this->makeCommentaire('Ok'); // 2 chars — invalide
        $this->manager->validate($commentaire);
    }

    // ════════════════════════════════════════════
    //  ✅ TEST 4 — Contenu exactement 5 chars (limite OK)
    // ════════════════════════════════════════════

    public function testContenuLimiteMinimum(): void
    {
        $commentaire = $this->makeCommentaire('Bravo'); // 5 chars — valide
        $this->assertTrue($this->manager->validate($commentaire));
    }

    // ════════════════════════════════════════════
    //  ❌ TEST 5 — Contenu trop long (> 500 chars)
    // ════════════════════════════════════════════

    public function testContenuTropLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le commentaire ne peut pas dépasser 500 caractères.');

        $commentaire = $this->makeCommentaire(str_repeat('A', 501)); // 501 chars — invalide
        $this->manager->validate($commentaire);
    }

    // ════════════════════════════════════════════
    //  ✅ TEST 6 — Contenu exactement 500 chars (limite haute OK)
    // ════════════════════════════════════════════

    public function testContenuLimiteMaximum(): void
    {
        $commentaire = $this->makeCommentaire(str_repeat('A', 500)); // 500 chars — valide
        $this->assertTrue($this->manager->validate($commentaire));
    }

    // ════════════════════════════════════════════
    //  ❌ TEST 7 — Publication manquante
    // ════════════════════════════════════════════

    public function testSansPublication(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Une publication est requise pour le commentaire.');

        $commentaire = $this->makeCommentaire(
            contenu:  'Commentaire valide ici.',
            avecUser: true,
            avecPub:  false // pas de publication
        );
        $this->manager->validate($commentaire);
    }

    // ════════════════════════════════════════════
    //  ❌ TEST 8 — User (auteur) manquant
    // ════════════════════════════════════════════

    public function testSansUser(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Un auteur est requis pour le commentaire.');

        $commentaire = $this->makeCommentaire(
            contenu:  'Commentaire valide ici.',
            avecUser: false, // pas de user
            avecPub:  true
        );
        $this->manager->validate($commentaire);
    }

    // ════════════════════════════════════════════
    //  ❌ TEST 9 — Contenu avec espaces seulement
    // ════════════════════════════════════════════

    public function testContenuEspacesSeuls(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le contenu du commentaire est obligatoire.');

        $commentaire = $this->makeCommentaire('     '); // espaces = vide
        $this->manager->validate($commentaire);
    }
}