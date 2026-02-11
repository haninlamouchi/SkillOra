<?php

namespace App\Controller;

use App\Entity\MembreGroupe;
use App\Form\MembreGroupeType;
use App\Repository\MembreGroupeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/membre/groupe')]
final class MembreGroupeController extends AbstractController
{
    #[Route(name: 'app_membre_groupe_index', methods: ['GET'])]
    public function index(MembreGroupeRepository $membreGroupeRepository): Response
    {
        return $this->render('membre_groupe/index.html.twig', [
            'membre_groupes' => $membreGroupeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_membre_groupe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $membreGroupe = new MembreGroupe();
        $form = $this->createForm(MembreGroupeType::class, $membreGroupe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($membreGroupe);
            $entityManager->flush();

            return $this->redirectToRoute('app_membre_groupe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('membre_groupe/new.html.twig', [
            'membre_groupe' => $membreGroupe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_membre_groupe_show', methods: ['GET'])]
    public function show(MembreGroupe $membreGroupe): Response
    {
        return $this->render('membre_groupe/show.html.twig', [
            'membre_groupe' => $membreGroupe,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_membre_groupe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MembreGroupe $membreGroupe, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MembreGroupeType::class, $membreGroupe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_membre_groupe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('membre_groupe/edit.html.twig', [
            'membre_groupe' => $membreGroupe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_membre_groupe_delete', methods: ['POST'])]
    public function delete(Request $request, MembreGroupe $membreGroupe, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$membreGroupe->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($membreGroupe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_membre_groupe_index', [], Response::HTTP_SEE_OTHER);
    }
}
