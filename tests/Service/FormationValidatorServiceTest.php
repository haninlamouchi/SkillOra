<?php

namespace App\Tests\Service;

use App\Entity\Formation;
use App\Service\FormationValidatorService;
use PHPUnit\Framework\TestCase;

class FormationValidatorServiceTest extends TestCase
{
    private FormationValidatorService $validator;

    protected function setUp(): void
    {
        $this->validator = new FormationValidatorService();
    }

    // =========================================================================
    // Rule 1 – isTitreValid
    // =========================================================================

    public function testTitreValide(): void
    {
        $formation = new Formation();
        $formation->setTitre('Introduction à Symfony');

        $this->assertTrue($this->validator->isTitreValid($formation));
    }

    public function testTitreVideInvalide(): void
    {
        $formation = new Formation();
        $formation->setTitre('');

        $this->assertFalse($this->validator->isTitreValid($formation));
    }

    public function testTitreTropLongInvalide(): void
    {
        $formation = new Formation();
        $formation->setTitre(str_repeat('a', 201));

        $this->assertFalse($this->validator->isTitreValid($formation));
    }

    public function testTitreExactement200CaracteresValide(): void
    {
        $formation = new Formation();
        $formation->setTitre(str_repeat('a', 200));

        $this->assertTrue($this->validator->isTitreValid($formation));
    }

    // =========================================================================
    // Rule 2 – isDescriptionValid
    // =========================================================================

    public function testDescriptionNullValide(): void
    {
        $formation = new Formation();
        $formation->setDescription(null);

        $this->assertTrue($this->validator->isDescriptionValid($formation));
    }

    public function testDescriptionSuffisantValide(): void
    {
        $formation = new Formation();
        $formation->setDescription('Apprenez les bases de PHP en 10 heures.');

        $this->assertTrue($this->validator->isDescriptionValid($formation));
    }

    public function testDescriptionTropCourteInvalide(): void
    {
        $formation = new Formation();
        $formation->setDescription('Court');

        $this->assertFalse($this->validator->isDescriptionValid($formation));
    }

    public function testDescriptionExactement10CaracteresValide(): void
    {
        $formation = new Formation();
        $formation->setDescription('1234567890');

        $this->assertTrue($this->validator->isDescriptionValid($formation));
    }

    // =========================================================================
    // Rule 3 – areDatesValid
    // =========================================================================

    public function testDatesValides(): void
    {
        $formation = new Formation();
        $formation->setDateDebut(new \DateTime('2026-01-01'));
        $formation->setDateFin(new \DateTime('2026-06-30'));

        $this->assertTrue($this->validator->areDatesValid($formation));
    }

    public function testDateFinAvantDateDebutInvalide(): void
    {
        $formation = new Formation();
        $formation->setDateDebut(new \DateTime('2026-06-01'));
        $formation->setDateFin(new \DateTime('2026-01-01'));

        $this->assertFalse($this->validator->areDatesValid($formation));
    }

    public function testDatesEgalesInvalide(): void
    {
        $formation = new Formation();
        $formation->setDateDebut(new \DateTime('2026-03-05'));
        $formation->setDateFin(new \DateTime('2026-03-05'));

        $this->assertFalse($this->validator->areDatesValid($formation));
    }

    public function testDateDebutNullValide(): void
    {
        $formation = new Formation();
        $formation->setDateDebut(null);
        $formation->setDateFin(new \DateTime('2026-06-30'));

        $this->assertTrue($this->validator->areDatesValid($formation));
    }

    // =========================================================================
    // Rule 4 – isPrixCoherent
    // =========================================================================

    public function testFormationGratuiteValide(): void
    {
        $formation = new Formation();
        $formation->setIsPaid(false);
        $formation->setPrix(null);

        $this->assertTrue($this->validator->isPrixCoherent($formation));
    }

    public function testFormationPayanteAvecPrixValide(): void
    {
        $formation = new Formation();
        $formation->setIsPaid(true);
        $formation->setPrix(29.99);

        $this->assertTrue($this->validator->isPrixCoherent($formation));
    }

    public function testFormationPayanteSansPrixInvalide(): void
    {
        $formation = new Formation();
        $formation->setIsPaid(true);
        $formation->setPrix(null);

        $this->assertFalse($this->validator->isPrixCoherent($formation));
    }

    public function testFormationPayantePrixZeroInvalide(): void
    {
        $formation = new Formation();
        $formation->setIsPaid(true);
        $formation->setPrix(0);

        $this->assertFalse($this->validator->isPrixCoherent($formation));
    }

    // =========================================================================
    // Rule 5 – isTermineeCoherente
    // =========================================================================

    public function testTermineeAvecDateDebutValide(): void
    {
        $formation = new Formation();
        $formation->setDateDebut(new \DateTime('2026-01-01'));
        $formation->setTerminee(true);

        $this->assertTrue($this->validator->isTermineeCoherente($formation));
    }

    public function testNonTermineeSansDateDebutValide(): void
    {
        $formation = new Formation();
        $formation->setDateDebut(null);
        $formation->setTerminee(false);

        $this->assertTrue($this->validator->isTermineeCoherente($formation));
    }

    public function testTermineeSansDateDebutInvalide(): void
    {
        $formation = new Formation();
        $formation->setDateDebut(null);
        $formation->setTerminee(true);

        $this->assertFalse($this->validator->isTermineeCoherente($formation));
    }
}
