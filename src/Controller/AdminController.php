<?php

namespace App\Controller;

use App\Entity\Conge;
use App\Entity\User;
use App\Form\CongeType;
use App\Repository\CongeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(CongeRepository $congeRepository, UserRepository $userRepository): Response
    {
        // Récupérer les statistiques pour le tableau de bord
        $totalUsers = $userRepository->count([]);
        $totalConges = $congeRepository->count([]);
        $congesEnAttente = $congeRepository->count(['statut' => 'en_attente']);
        $congesApprouves = $congeRepository->count(['statut' => 'approuve']);
        $congesRefuses = $congeRepository->count(['statut' => 'refuse']);
        
        // Utilisez le nom correct du champ avec underscore
        $recentConges = $congeRepository->findBy(
            ['statut' => 'en_attente'], 
            ['date_debut' => 'DESC'], 
            5
        );

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalConges' => $totalConges,
            'congesEnAttente' => $congesEnAttente,
            'congesApprouves' => $congesApprouves,
            'congesRefuses' => $congesRefuses,
            'recentConges' => $recentConges,
        ]);
    }

    #[Route('/conges', name: 'admin_conges')]
    public function conges(CongeRepository $congeRepository): Response
    {
        // Utilisez le nom correct du champ avec underscore
        $conges = $congeRepository->findBy([], ['date_debut' => 'DESC']);

        return $this->render('admin/conges.html.twig', [
            'conges' => $conges,
        ]);
    }

    #[Route('/conges/{id}/edit', name: 'admin_conge_edit')]
public function editConge(Request $request, int $id, CongeRepository $congeRepository, EntityManagerInterface $entityManager): Response
{
    $conge = $congeRepository->find($id);
    
    if (!$conge) {
        throw $this->createNotFoundException('Congé non trouvé');
    }
    
    // Le reste de votre code...
    $form = $this->createForm(CongeType::class, $conge);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();

        $this->addFlash('success', 'Le congé a été modifié avec succès.');
        return $this->redirectToRoute('admin_conges');
    }

    return $this->render('admin/conge_edit.html.twig', [
        'conge' => $conge,
        'form' => $form->createView(),
    ]);
}
    #[Route('/conges/{id}/approve', name: 'admin_conge_approve')]
    public function approveConge(Conge $conge, EntityManagerInterface $entityManager): Response
    {
        $conge->setStatut('approuve');
        $entityManager->flush();

        $this->addFlash('success', 'Le congé a été approuvé.');
        return $this->redirectToRoute('admin_conges');
    }

    #[Route('/conges/{id}/reject', name: 'admin_conge_reject')]
    public function rejectConge(Conge $conge, EntityManagerInterface $entityManager): Response
    {
        $conge->setStatut('refuse');
        $entityManager->flush();

        $this->addFlash('success', 'Le congé a été refusé.');
        return $this->redirectToRoute('admin_conges');
    }

    #[Route('/users', name: 'admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/conges/new', name: 'admin_conge_new')]
public function newConge(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
{
    $conge = new Conge();
    $form = $this->createForm(CongeType::class, $conge, [
        'include_user' => true
    ]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($conge);
        $entityManager->flush();

        $this->addFlash('success', 'Le congé a été créé avec succès.');
        return $this->redirectToRoute('admin_conges');
    }

    return $this->render('admin/conge_new.html.twig', [
        'form' => $form->createView(),
    ]);
}

    #[Route('/conges/{id}/delete', name: 'admin_conge_delete')]
public function deleteConge(Conge $conge, EntityManagerInterface $entityManager): Response
{
    $entityManager->remove($conge);
    $entityManager->flush();

    $this->addFlash('success', 'Le congé a été supprimé avec succès.');
    return $this->redirectToRoute('admin_conges');
}

#[Route('/users/{id}/add-role/{role}', name: 'admin_user_add_role', methods: ['POST'])]
public function addUserRole(int $id, string $role, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
{
    $user = $userRepository->find($id);
    
    if (!$user) {
        throw $this->createNotFoundException('Utilisateur non trouvé');
    }
    
    // Vérifier que le rôle est valide
    $allowedRoles = ['ROLE_ADMIN'];
    if (!in_array($role, $allowedRoles)) {
        throw $this->createAccessDeniedException('Rôle non autorisé');
    }
    
    // Ajouter le rôle s'il n'existe pas déjà
    $roles = $user->getRoles();
    if (!in_array($role, $roles)) {
        $roles[] = $role;
        $user->setRoles($roles);
        $entityManager->flush();
        $this->addFlash('success', 'Le rôle administrateur a été ajouté à ' . $user->getPrenom() . ' ' . $user->getNom());
    }
    
    return $this->redirectToRoute('admin_users');
}

#[Route('/users/{id}/remove-role/{role}', name: 'admin_user_remove_role', methods: ['POST'])]
public function removeUserRole(int $id, string $role, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
{
    $user = $userRepository->find($id);
    
    if (!$user) {
        throw $this->createNotFoundException('Utilisateur non trouvé');
    }
    
    // Vérifier que le rôle est valide
    $allowedRoles = ['ROLE_ADMIN'];
    if (!in_array($role, $allowedRoles)) {
        throw $this->createAccessDeniedException('Rôle non autorisé');
    }
    
    // Retirer le rôle s'il existe
    $roles = $user->getRoles();
    if (($key = array_search($role, $roles)) !== false) {
        unset($roles[$key]);
        $user->setRoles(array_values($roles)); // Réindexer le tableau
        $entityManager->flush();
        $this->addFlash('success', 'Le rôle administrateur a été retiré de ' . $user->getPrenom() . ' ' . $user->getNom());
    }
    
    return $this->redirectToRoute('admin_users');
}

}

