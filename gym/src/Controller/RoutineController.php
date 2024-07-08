<?php


namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Routine;
use App\Form\RoutineType;
use App\Entity\User;
    
class RoutineController extends AbstractController
{
    private $em;
            
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    // Ver Rutinas
    /**
    * @Route("/routine", name="routine_index")
    */
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You are not logged in.');
        }    
        // $routines = $this->em->getRepository(Routine::class)->findBy(['user' => $user]);
        
        $roles = $user->getRoles();

        if (empty($roles)) {
            throw $this->createAccessDeniedException('User does not have any roles assigned.');
        }

        $routines = [];

        if (in_array('ROLE_COACH', $roles, true)) {
            // Obtener IDs de los usuarios asesorados por el coach
            $assistedUserIds = $this->em->getRepository(User::class)->createQueryBuilder('u')
                ->select('u.id')
                ->where('u.coach = :coachId')
                ->setParameter('coachId', $user->getId())
                ->getQuery()
                ->getResult();

            $assistedUserIds = array_column($assistedUserIds, 'id');

            // Si es ROLE_COACH, obtener rutinas creadas por él mismo o por sus asesorados
            $routines = $this->em->getRepository(Routine::class)->createQueryBuilder('r')
                ->where('r.user = :coachId')
                ->orWhere('r.user IN (:assistedUserIds)')
                ->setParameter('coachId', $user->getId())
                ->setParameter('assistedUserIds', $assistedUserIds)
                ->getQuery()
                ->getResult();
        }elseif (in_array('ROLE_USER', $roles, true)) {
            // Si es ROLE_USER, obtener rutinas donde el usuario es el creador o el asignado
            $routines = $this->em->getRepository(Routine::class)->createQueryBuilder('r')
                ->where('r.user = :userId')
                ->orWhere('r.assignedTo = :userId')
                ->setParameter('userId', $user->getId())
                ->getQuery()
                ->getResult();
        } 
        
        $routinesData = [];
        
        foreach ($routines as $routine) {
            $exercises = [];
            foreach ($routine->getExercises() as $exercise) {
                $exercises[] = [
                    'id' => $exercise->getId(),
                    'name' => $exercise->getName(),
                    'repetitions' => $exercise->getRepetitions(),
                    'series' => $exercise->getSeries(),
                ];
            }
            $routinesData[] = [
                'id' => $routine->getId(),
                'name' => $routine->getName(),
                'focus' => $routine->getFocus(),
                'assignedTo' => $routine->getAssignedTo() ? $routine->getAssignedTo()->getEmail() : 'Not assigned', // Ajusta según la estructura de tu User entity
                'user' => $routine->getUser()->getEmail(), // Ajusta según la estructura de tu User entity
                'exercises' => $exercises,
            ];
        }
    
        return $this->render('routine/index.html.twig', [
            'routines' => $routinesData,
        ]);
    }
    
    // Crear Rutinas
    /**
    * @Route("/routine/new", name="routine_new", methods={"GET", "POST"})
    */
    public function newRutine(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You are not logged in.');
        }
    
        $routine = new Routine();

        // Asignación automática 
        if (in_array('ROLE_USER', $user->getRoles(), true)) {
            $routine->setUser($user);
        } elseif (in_array('ROLE_COACH', $user->getRoles(), true)) {
            $routine->setUser($user);
        }
        
        // Obtener usuarios asignados al coach si es ROLE_COACH
        $assignedUsers = [];
        if (in_array('ROLE_COACH', $user->getRoles(), true)) {
            $assignedUsers = $this->em->getRepository(User::class)->findBy(['coach' => $user]);
        }

        $form = $this->createForm(RoutineType::class, $routine, [
            'user' => $user,
            'users' => $assignedUsers,
        ]);
        
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Asignar el usuario asignado solo si es ROLE_COACH
            if (in_array('ROLE_COACH', $user->getRoles(), true)) {
                $assignedUser = $form->get('assignedTo')->getData();
                if ($assignedUser instanceof User) {
                    $routine->setAssignedTo($assignedUser); // Asigna la rutina al usuario elegido
                }
            }
            foreach ($routine->getExercises() as $exercise) {
                $exercise->setRoutine($routine);
                $this->em->persist($exercise);
            }
    
            $this->em->persist($routine);
            $this->em->flush();
    
            return $this->redirectToRoute('app_routine');
        }
    
        return $this->render('routine/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Eliminar Rutina
    /**
     * @Route("/routine/{id}/delete", name="routine_delete", methods={"POST"})
     */
    public function deleteRoutine(Routine $routine): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You are not logged in.');
        }

        // Verificar que el usuario actual sea el propietario de la rutina
        if ($routine->getUser() !== $user) {
            $this->addFlash('error', 'You are not allowed to delete this routine.');

            return $this->redirectToRoute('app_routine');
        }

        $this->em->remove($routine);
        $this->em->flush();

        $this->addFlash('success', 'Routine deleted successfully.');

        return $this->redirectToRoute('app_routine');
    }

    // Editar Rutina
    /**
     * @Route("/routine/{id}/edit", name="routine_edit", methods={"GET", "POST"})
     */
    public function editRoutine(Request $request, Routine $routine): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You are not logged in.');
        }

        // Obtener usuarios asignados al coach si es ROLE_COACH
        $assignedUsers = [];
        if (in_array('ROLE_COACH', $user->getRoles(), true)) {
            $assignedUsers = $this->em->getRepository(User::class)->findBy(['coach' => $user]);
        }

        // Crear el formulario de edición
        $form = $this->createForm(RoutineType::class, $routine, [
            'user' => $user,
            'users' => $assignedUsers,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Asignar el usuario asignado solo si es ROLE_COACH
            if (in_array('ROLE_COACH', $user->getRoles(), true)) {
                $assignedUser = $form->get('assignedTo')->getData();
                if ($assignedUser instanceof User) {
                    $routine->setAssignedTo($assignedUser); // Asigna la rutina al usuario elegido
                }
            }
            foreach ($routine->getExercises() as $exercise) {
                $exercise->setRoutine($routine);
                $this->em->persist($exercise);
            }

            $this->em->persist($routine);
            $this->em->flush();

            return $this->redirectToRoute('app_routine');
        }

        return $this->render('routine/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}
    
