<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Formation;
use Twig\Cache\NullCache;
use App\Entity\Candidature;
use OpenApi\Attributes as OA;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
//  use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Routing\Annotation\Route;
//  use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted as AttributeIsGranted;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserController extends AbstractController
{
    private $manager;

    private $user;

    public function __construct(EntityManagerInterface $manager, UserRepository $user )
    {
        $this->manager = $manager;
        $this->user = $user;
    }
    //Route des utilisateurs deconnecté
    // #[Route('/public', name:'app_public', methods:'GET')]
    // public function accueil(){
    //     return new JsonResponse([
    //         'status'=>true,
    //         'message'=>'Utilisateur déconnecté avec succès'
    // ]);
    // }
    //Création d'un utilisateur
    
    #[Route('/user', name: 'app_user', methods:'POST')]
    public function store(Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {
       $data = json_decode($request->getContent(), true);
       $email = $data['email'];
       $password = $data['password'];
       $name = $data['name'];
       $grade = $data['grade'];
       //Vérification de l'unicité de l'email
    //Vérification de l'unicité de l'email
    $email_exists  = $this->user->findOneByEmail($email);
    if($email_exists){
        // return $this->json('', 200);
        return new JsonResponse(
            [
                'status'=>false,
                'message'=>"L'email existe déjà"
            ]
            );
    }
    else {
        $user = new User();
        $user->setEmail($email)
            
            ->setName($name)
            ->setGrade($grade);
            // $user->setPassword(
            //     $userPasswordHasher->hashPassword(
            //         $user,
            //         $form->get('plainPassword')->getData()
            //     )
            // );
             // Attribution des rôles en fonction du grade
        if ($grade === 'Admin') {
            $user->setRoles(['ROLE_ADMIN']);
        } else if ($grade === 'Candidat') {
            $user->setRoles(['ROLE_CANDIDAT']);
        } else {
            $user->setRoles(['ROLE_USER']); // Rôle par défaut
        }
            $user->setPassword($userPasswordHasher->hashPassword(
                $user,
                $password
            ));
            $this->manager->persist($user);
            $this->manager->flush();
            return new JsonResponse(
                [
                    'status'=>true,
                    'message'=>"Utilisateur enregistré avec succès"
                ]
                );
    }
}// type: 'array',
//items: new OA\Items(ref: new Model(type: User::class))
//Récupérer la liste des utilisateurs
#[Route('/api/getAllUsers', name: 'get_allusers', methods:'GET')]
#[OA\Response(
    response: 200,
    description: 'Renvoie la liste de tous les utilisateurs',
    content: new OA\JsonContent()
)]
#[OA\Tag(name: 'user')]
#[Route('/api/getAllUsers', name: 'get_allusers', methods:'GET')]
public function getAllUsers(): Response
{
    $user = $this->getUser();
    // dd($user);
    if($this->isGranted('ROLE_ADMIN')){
        $users = $this->user->findAll();
        return new JsonResponse(
            [
                'status'=>true,
                'message'=>"La liste des utilisateurs est :",
                'Utilisateurs' => [$users]
                
            ]
        );
       
       
    } else {
        return new JsonResponse(
            [
                'status'=>true,
                'message'=>"Vous ne pouvez pas acceder à cette page"
                
            ]
        );
    }
    
}

/**
 * @OA\Post(
 *     path="/api/addFormation",
 *     summary="Ajouter une nouvelle formation",
 *     description="Permet à un administrateur d'ajouter une nouvelle formation",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name", "description", "duration"},
 *             @OA\Property(property="name", type="string", example="Nom de la Formation"),
 *             @OA\Property(property="description", type="string", example="Description de la Formation"),
 *             @OA\Property(property="duration", type="string",  example="6 mois")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Formation ajoutée avec succès"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Données invalides"
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Accès refusé"
 *     )
 * )
 */
#[Route('/api/addFormation', name: 'add_formations', methods:'POST')]
public function formationStore(Request $request): Response {
    $data = json_decode($request->getContent(), true);
    $name = $data['name'];
    $duration = $data['duration'];
    $description = $data['description'];
    if($this->isGranted('ROLE_ADMIN')){
        $formation = new Formation();
        $formation->setName($name)
                  ->setDuration($duration)
                  ->setDescription($description);
        $this->manager->persist($formation);
        $this->manager->flush();
        return new JsonResponse(
            [
                'status'=>true,
                'message'=>"Formation ajoutée avec succès"
            ]
        );
    } else {
        return new JsonResponse(
            [
                'status'=>true,
                'message'=>"Vous ne pouvez pas acceder à cette page"
                
            ]
        );
    }
}
//Modifier une formation
#[Route('/api/updateFormation/{id}', name: 'api_updateFormation', methods: 'PUT')]
public function updateFormation(Request $request, EntityManagerInterface $entityManager,$id) : Response{
    $data = json_decode($request->getContent(), true);
    // dd($data);
   

    if($this->isGranted('ROLE_ADMIN')){
        $formation = $entityManager->getRepository(Formation::class)->findOneBy(['id' => $id]);
        // dd($formation);

        if (isset($data['name']) && isset($data['duration']) && isset($data['description'])) {
            $formation->setName($data['name']);
            $formation->setDuration($data['duration']);
            $formation->setDescription($data['description']);
            $this->manager->persist($formation);
            $this->manager->flush();
            return new JsonResponse(
                        [
                            'status'=>true,
                            'message'=>"Formation modifié avec succès"
                       ]
            );
        }
        elseif (isset($data['name']) && isset($data['duration'])) {
            $formation->setName($data['name']);
            $formation->setDuration($data['duration']);
            $formation->setDescription($data['description']);
            $this->manager->persist($formation);
            $this->manager->flush();
            return new JsonResponse(
                        [
                            'status'=>true,
                            'message'=>"Formation modifié avec succès"
                       ]
            );
        }
        if (isset($data['duration']) && isset($data['description'])) {
            $formation->setName($data['name']);
            $formation->setDuration($data['duration']);
            $formation->setDescription($data['description']);
            $this->manager->persist($formation);
            $this->manager->flush();
            return new JsonResponse(
                        [
                            'status'=>true,
                            'message'=>"Formation modifié avec succès"
                       ]
            );
        }
        elseif(isset($data['name'])  && isset($data['description'])) {
            $formation->setName($data['name']);
            $formation->setDuration($data['duration']);
            $formation->setDescription($data['description']);
            $this->manager->persist($formation);
            $this->manager->flush();
            return new JsonResponse(
                        [
                            'status'=>true,
                            'message'=>"Formation modifié avec succès"
                       ]
            );
        }
        elseif(isset($data['name'])) {
            $formation->setDuration($data['name']);
            $this->manager->persist($formation);
            $this->manager->flush();
            return new JsonResponse(
                [
                    'status'=>true,
                    'message'=>"Le nom de la formation a été  modifié avec succès"
               ]
            );
        }
        elseif(isset($data['duration'])) {
            $formation->setDuration($data['duration']);
            $this->manager->persist($formation);
            $this->manager->flush();
            return new JsonResponse(
                [
                    'status'=>true,
                    'message'=>"La durée de la formation a été  modifiée avec succès"
               ]
            );
        }

        elseif(isset($data['description'])) {
            $formation->setDescription($data['description']);
            $this->manager->persist($formation);
            $this->manager->flush();
            return new JsonResponse(
                [
                    'status'=>true,
                    'message'=>"La description de la formation a été  modifiée avec succès"
               ]
            );
        } else {
            return new JsonResponse(
                [
                    'status'=>true,
                    'message'=>"Aucune modification n'a été apportée"
               ]
            );
        }
       
       
        
    } else {
        return new JsonResponse(
            [
                'status'=>true,
                'message'=>"Vous ne pouvez pas acceder à cette page"
                
            ]
        );
    }
}
//La méthode delete Formation
#[Route('/api/deleteFormation/{id}', name:'api_deleteFormation', methods:'DELETE')]
public function deleteFormation( EntityManagerInterface $entityManager, $id){
    if($this->isGranted('ROLE_ADMIN')){
        $formation = $entityManager->getRepository(Formation::class)->findOneBy(['id' => $id]);
        // dd($formation);
        $this->manager->remove($formation);

        $this->manager->flush();

        return new JsonResponse(
            [
                'status'=>true,
                'response'=>'Formation supprimée avec succès'
            ]
            );
    } else {
        return new JsonResponse(
            [
                'status'=>true,
                'message'=>"Vous ne pouvez pas acceder à cette page"
                
            ]
        );
    }
}
//La méthode candidater 
#[Route('/api/candidate', name:'api_candidate', methods: 'POST')]
public function candidate(EntityManagerInterface $entityManager, Request $request){
    if($this->isGranted('ROLE_CANDIDAT')){
        $data = json_decode($request->getContent(), true);
        $scholarship = $data['scholarship'];
        $formationNameGiven = $data['formation'];
        $status = $data['status'];
        $formation = $entityManager->getRepository(Formation::class)->findOneBy(['name' => $formationNameGiven]);
        // dd($formation);
        $user = $this->getUser();
        // dd($user);
        $candidatures = $entityManager->getRepository(Candidature::class)->findAll();
        // dd($candidatures);
        foreach($candidatures as $candidature){
            if($candidature->getUser() == $user && $candidature->getFormation() == $formation){
                return new JsonResponse(
                    [
                        'status'=>true,
                        'message'=>"Vous avez déjà candidater pour cette formation"
                   ]
                );
            }else{
                $candidature = new Candidature();
        
                $candidature->setScholarship($scholarship)
                    ->setFormation($formation)
                    ->setStatus($status)
                    ->setUser($user);
                // dd($candidature);
                $this->manager->persist($candidature);
                $this->manager->flush();
                return new JsonResponse(
                [
                    'status'=>true,
                    'message'=>"La candidature a été enregistrée avec succès"
               ]
            );
            }
        }
        
    } else {
        return new JsonResponse(
            [
                'status'=>true,
                'message'=>"Vous ne pouvez pas acceder à cette page"
                
            ]
        );
    }
}
//Accepter une candidature
#[Route('/api/acceptCandidate/{id}', name:'api_acceptCandidate', methods:'PUT')]
public function acceptCandidate(EntityManagerInterface $entityManager, $id){
    if($this->isGranted('ROLE_ADMIN')){
        $candidature = $entityManager->getRepository(Candidature::class)->findOneBy(['id' => $id]);
        $candidature->setStatus('Acceptée');
        $this->manager->persist($candidature);
        $this->manager->flush();
        return new JsonResponse(
            [
                'status'=>true,
                'message'=>"La candidature a été acceptée avec succès"
            ]
        );
    } else {
        return new JsonResponse(
            [
                'status'=>true,
                'message'=>"Vous ne pouvez pas acceder à cette page"
                
            ]
        );
    }
}
//Refuser une candidature
#[Route('/api/denyCandidate/{id}', name:'api_denyCandidate', methods:'PUT')]
public function denyCandidate(EntityManagerInterface $entityManager, $id){
    if($this->isGranted('ROLE_ADMIN')){
        $candidature = $entityManager->getRepository(Candidature::class)->findOneBy(['id' => $id]);
        $candidature->setStatus('Refusée');
        $this->manager->persist($candidature);
        $this->manager->flush();
        return new JsonResponse(
            [
                'status'=>true,
                'message'=>"La candidature a été refusée avec succès"
            ]
        );
    } else {
        return new JsonResponse(
            [
                'status'=>true,
                'message'=>"Vous ne pouvez pas acceder à cette page"
                
            ]
        );
    }
}
//Afficher la liste de toutes les candidatures
#[Route('/api/listCandidate', name: 'api_listCandidate', methods: 'GET')]
public function listCandidate(EntityManagerInterface $entityManager){
    if($this->isGranted('ROLE_ADMIN')){
        $candidatures = $entityManager->getRepository(Candidature::class)->findAll();
        return new JsonResponse(
            [
                'status'=>true,
                'message'=>"La liste des candidatures a été retournées avec succès",
                'candidatures' => $candidatures
            ]
        );
    }else {
        return new JsonResponse(
            [
                'status'=>true,
                'message'=>"Vous ne pouvez pas acceder à cette page"
                
            ]
        );
    }
}
//Afficher la liste des candidatures acceptées
#[Route('/api/listCandidateAccepted', name: 'api_listCandidateAccepted', methods: 'GET')]
public function listCandidateAccepted(EntityManagerInterface $entityManager){
    if($this->isGranted('ROLE_ADMIN')){
        $candidatures = $entityManager->getRepository(Candidature::class)->findBy(['status'=>'Acceptée']);
        foreach($candidatures as $candidature){
            if($candidature->getStatus() == 'Acceptée'){
                return new JsonResponse(
                    [
                        'status'=>true,
                        'message'=>"La liste des candidatures acceptées a été retournées avec succès",
                        'candidatures' => [
                            $candidature->getId(), 
                            $candidature->getScholarship(), 
                            $candidature->getStatus()
                            ]
                    ]
                );
            }
        }
        
    }else {
        return new JsonResponse(
            [
                'status'=>true,
                'message'=>"Vous ne pouvez pas acceder à cette page"
                
            ]
        );
    }
}
//Afficher la liste des candidatures refusées
#[Route('/api/listCandidateDenied', name: 'api_listCandidateDenied', methods: 'GET')]
public function listCandidateDenied(EntityManagerInterface $entityManager){
    if($this->isGranted('ROLE_ADMIN')){
        $candidatures = $entityManager->getRepository(Candidature::class)->findBy(['status'=>'Refusée']);
        //  dd($candidatures);
        foreach($candidatures as $candidature){
            // dd($candidature);
            if($candidature->getStatus() == "Refusée"){
                return new JsonResponse(
                    [
                        'status'=>true,
                        'message'=>"La liste des candidatures refusées a été retournées avec succès",
                        'candidatures' => [
                            $candidature->getId(), 
                            $candidature->getScholarship(), 
                            $candidature->getStatus()
                            ]
                    ]
                );
            }
        }
        
    }else {
        return new JsonResponse(
            [
                'status'=>true,
                'message'=>"Vous ne pouvez pas acceder à cette page"
                
            ]
        );
    }
}
//logout
// #[Route('/api/logout', name:'api_logout', methods:'POST')]
// public function logout(JWTTokenManagerInterface $JWTManager, Request $request)
// {
//     // Vérifiez que l'utilisateur est authentifié
//     if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
//         return new JsonResponse(['message' => 'Vous n\'êtes pas authentifié'], 401);
//     }

//     // Récupérez le token JWT de la requête
//     $token = $request->headers->get('Authorization', null);

//     if (!$token) {
//         return new JsonResponse(['message' => 'Token JWT manquant'], 400);
//     }

//     // Invalidez le token JWT
//     $JWTManager->invalidate($token);

//     // Renvoyez une réponse JSON confirmant la déconnexion réussie
//     return new JsonResponse(['message' => 'Déconnexion réussie'], 200);
// }
}

