<?php

namespace App\Controller\Security;

use App\Entity\Album;
use App\Entity\Candidature;
use App\Entity\Contact;
use App\Entity\Order;
use App\Entity\Produit;
use App\Form\CandidatureType;
use App\Form\ContactFormType;
use App\Form\OrderType;
use App\Repository\ImageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
use App\Form\RegistrationType;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Form\UserType;
use App\Form\changePasswordType;
use App\Repository\UserRepository;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


/**
 * @Route("/{_locale}")
 */
class securityController extends AbstractController
{
    /**
     * @Route("/", name="Accueil")
     */
    public function accueil(Request $request)
    {
        return $this->render('site/accueil.html.twig');
    }

    /**
     * @Route("/About", name="About")
     */
    public function about(Request $request)
    {
        return $this->render('site/about.html.twig');
    }

    /**
     * @Route("/Logistique", name="Logistique")
     */
    public function planning(Request $request)
    {
        return $this->render('site/logistique.html.twig');
    }

    /**
     * @Route("/Distribution", name="Distribution")
     */
    public function improve(Request $request)
    {
        return $this->render('site/distribution.html.twig');
    }

    /**
     * @Route("/Promotion", name="Promotion")
     */
    public function securite(Request $request)
    {
        return $this->render('site/promotion.html.twig');
    }

    /**
     * @Route("/Actualite", name="Actualite")
     */
    public function actualite(Request $request)
    {
        return $this->render('site/actualite.html.twig');
    }

    /**
     * @Route("/Contact", name="Contact")
     */
    public function contact(Request $request)
    {
        $contact = new Contact();
        $form = $this->createForm(ContactFormType::class, $contact);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($contact);
                $em->flush();
                $this->addFlash('notice', 'Message enregistrée avec succée');
                return $this->redirectToRoute('Contact');
            }
        }
        return $this->render('site/contact.html.twig',
            [
                'form' => $form->createView(),
            ]);
    }

    /**
     * @Route("/Produits", name="Produits")
     */
    public function produits(Request $request)
    {
        return $this->render('site/produits.html.twig');
    }

    /**
     * @Route("/Carriere_Candidature", name="Carriere_candidature")
     */
    public function carriere(Request $request)
    {
        $candidature = new Candidature();
        $form = $this->createForm(CandidatureType::class, $candidature);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {

                $em = $this->getDoctrine()->getManager();
                //$candidature->getCv()->upload($candidature);
                $file = $form->get('cv')->getData();
                if ($file) {
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                    // Move the file to the directory where brochures are stored
                    try {
                        $file->move(
                            $this->getParameter('upload_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        // ... handle exception if something happens during file upload
                    }

                    // updates the 'brochureFilename' property to store the PDF file name
                    // instead of its contents
                    $candidature->setCvfile($newFilename);
                    //$candidature->getCv()->setUrl($fileName);
                    $em->persist($candidature);
                    $em->flush();
                    $this->addFlash('notice', 'Candidature enregistrée avec succée');
                    return $this->redirectToRoute('Carriere_candidature');
                }
            }
        }
        return $this->render('site/carrieres.html.twig',
            [
                'form' => $form->createView(),
            ]);
    }


    /**
     * @Route("/Carriere_Offer", name="Carriere_offre")
     */
    public function offre(Request $request)
    {
        return $this->render('site/carriere_offres.html.twig');
    }

    /**
     * @Route("/Carriere_Culture", name="Carriere")
     */
    public function culture(Request $request)
    {
        return $this->render('site/carriere_culture.html.twig');
    }

    /**
     * @Route("/Gallery", name="Gallery")
     */
    public function photo(Request $request)
    {
        $albums = $this->getDoctrine()->getRepository(Album::class)->findAll();
        return $this->render('site/galerie.html.twig', [
            'albums' => $albums,
        ]);
    }

    /**
     * @Route("/Gallery/Photo/Album/{album}", name="Gallery_photo_album_view")
     */
    public function photo_reservoir(Request $request, Album $album, ImageRepository $repository)
    {
        return $this->render('site/reservoir.html.twig', [
            'album' => $album,
            'images' => $repository->findBy(['album' => $album]),
        ]);
    }

    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        // md5() reduces the similarity of the file names generated by
        // uniqid(), which is based on timestamps
        return md5(uniqid());
    }


    /**
     * @Route("/Order/{produit}", name="order")
     */
    public function order(Request $request, Produit $produit)
    {
        $order = new Order();
        $order->setProduit($produit);
        $form = $this->createForm(OrderType::class, $order);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {

                $em = $this->getDoctrine()->getManager();
                $nomcom = $em->getRepository(Order::class)->findAll();
                $order->setNumero(count($nomcom) + 1);// nombre des commamdes +1 pour la nouvelle commande
                $em->persist($order);
                $em->flush();
                $this->addFlash('notice', 'Commande enregistrée avec succée');
                $response = $this->redirectToRoute('order', ['produit' => $produit->getId()]);
                $response->setSharedMaxAge(0);
                $response->headers->addCacheControlDirective('no-cache', true);
                $response->headers->addCacheControlDirective('no-store', true);
                $response->headers->addCacheControlDirective('must-revalidate', true);
                $response->setCache([
                    'max_age' => 0,
                    'private' => true,
                ]);
                return $response;
            }
        }
        $response = $this->render('site/order.html.twig',
            [
                'form' => $form->createView(),
            ]);
        $response->setSharedMaxAge(0);
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->addCacheControlDirective('no-store', true);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->setCache([
            'max_age' => 0,
            'private' => true,
        ]);
        return $response;
    }
//
//    /**
//     * @Route("/registration", name="security_register")
//     */
//    public function new(Request $request, UserPasswordEncoderInterface $encoder, TokenGeneratorInterface $tokenGenerator, \Swift_Mailer $mail)
//    {
//      if($this->get('security.authorization_checker')->isGranted('ROLE_CONSEILLER'))
//       {
//            $manager = $this->getDoctrine()->getManager();
//            $user = new User();
//            $form = $this->createForm(RegistrationType::class, $user);
//            $form->handleRequest($request);
//
//            if ($form->isSubmitted() && $form->isValid()) {
//                 $hashpass = $encoder->encodePassword($user, 'Passer2023');
//                 //$hashpass = $encoder->encodePassword($user, $user->getPassword());
//                 //$password = $user->getPassword();
//                 $user->setPassword($hashpass);
//                 $user->setusername($user->getNom());
//                switch($user->getFonction()){
//
//                    case "Conseiller":{  $user->setRoles(['ROLE_CONSEILLER']); break;}
//                    case "Administrateur":{  $user->setRoles(['ROLE_ADMIN']); break;}
//
//                }
//                 // envoie mail
//                 $token = $tokenGenerator->generateToken();
//                 $user->setResetToken($token);
//                 $manager->persist($user);
//                 $manager->flush();
//                 $url = $this->generateUrl('security_activation', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
//
//                 $message = (new \Swift_Message('Activation compte utilisateur'))
//                 ->setFrom('easyschool@jolofsoft.com')
//                 ->setTo($user->getEmail())
//                 ->setBody("Cliquez sur le lien suivant pour activer votre compte utilisasateur ".$url, 'text/html');
////                $message = (new \Swift_Message('Activation compte utilisateur'))
////                 ->setFrom('Hajjalbayt@euclideservices.com')
////                 ->setTo($user->getEmail())
////                 ->setBody($this->renderView('licence/facture.html.twig'), 'text/html');
//
//                 $mail->send($message);
//                 // fin envoie mail
//
//                $this->addFlash('notice', 'Utilisateur cree, un mail a ete envoye a son adresse mail pour l\'activation du compte');
//                return $this->redirectToRoute('security_profile');
//            }
//
//            $response = $this->render('security/security/index.html.twig', [
//                'form' => $form->createView(),
//            ]);
//            $response->setSharedMaxAge(0);
//            $response->headers->addCacheControlDirective('no-cache', true);
//            $response->headers->addCacheControlDirective('no-store', true);
//            $response->headers->addCacheControlDirective('must-revalidate', true);
//            $response->setCache([
//                'max_age' => 0,
//                'private' => true,
//            ]);
//            return $response;
//      }
//        else
//        {
//            $this->addFlash('notice', 'Vous n\'avez pas le droit d\'accede a cette partie de l\'application');
//            return $this->redirectToRoute('security_login');
//        }
//    }
//
//    /**
//     * @Route("/login", name="security_login")
//     */
//    public function login(AuthenticationUtils $auth)
//    {
//        $error = $auth->getLastAuthenticationError();
//        $last_user = $auth->getLastUsername();
//
//        $response = $this->render('security/security/login.html.twig',[
//            'error' => $error,
//            'last_user' => $last_user,
//        ]);
//        $response->setSharedMaxAge(0);
//        $response->headers->addCacheControlDirective('no-cache', true);
//        $response->headers->addCacheControlDirective('no-store', true);
//        $response->headers->addCacheControlDirective('must-revalidate', true);
//        $response->setCache([
//            'max_age' => 0,
//            'private' => true,
//        ]);
//        return $response;
//    }
//    /**
//     * @Route("/logout", name="security_logout")
//     */
//    public function logout()
//    {
//
//    }
//    /**
//     * @Route("/profile", name="security_profile")
//     */
//    public function profile()
//    {
//       if($this->getUser() !== null)
//       {
//
//           $response = $this->render('security/security/profile.html.twig',[
//               'user' => $this->getUser(),
//           ]);
//        $response->setSharedMaxAge(0);
//        $response->headers->addCacheControlDirective('no-cache', true);
//        $response->headers->addCacheControlDirective('no-store', true);
//        $response->headers->addCacheControlDirective('must-revalidate', true);
//        $response->setCache([
//            'max_age' => 0,
//            'private' => true,
//        ]);
//        return $response;
//       }
//       else
//       {
//           $this->addFlash('notice', 'Vous n\'avez pas le droit d\'accede a cette partie de l\'application');
//           return $this->redirectToRoute('security_login');
//       }
//    }
//
//    /**
//     * @Route("/edit_profile", name="security_profile_edit")
//     */
//    public function edit(Request $request)
//    {
//        if($this->getUser() !== null)
//        {
//        $em = $this->getDoctrine()->getManager();
//        $user = $em->getRepository(User::class)->find($this->getUser()->getId());
//        $form = $this->createForm(UserType::class, $user);
//        $form->remove('username');
//
//        $form->handleRequest($request);
//
//        if($form->isSubmitted() && $form->isValid())
//        {
//            $this->getDoctrine()->getManager()->flush();
//            $this->addFlash('notice', 'Profil modifié avec succès');
//            return $this->redirectToRoute('security_profile', ['id' => $user->getId()]);
//
//        }
//        $response = $this->render('security/security/edit.html.twig',[
//            'form' => $form->createView(),
//        ]);
//        $response->setSharedMaxAge(0);
//        $response->headers->addCacheControlDirective('no-cache', true);
//        $response->headers->addCacheControlDirective('no-store', true);
//        $response->headers->addCacheControlDirective('must-revalidate', true);
//        $response->setCache([
//            'max_age' => 0,
//            'private' => true,
//        ]);
//        return $response;
//    }
//    else
//    {
//        $this->addFlash('notice', 'Vous n\'avez pas le droit d\'accede a cette partie de l\'application');
//        return $this->redirectToRoute('security_login');
//    }
//    }
//
//
//
//    /**
//     * @Route("/ChangePassword", name="security_change_password")
//     */
//    public function change(Request $request, UserPasswordEncoderInterface $encoder)
//    {
//        if($this->getUser() !== null)
//        {
//        $userinit =  new User();
//        $form = $this->createForm(changePasswordType::class, $userinit);
//
//        $form->handleRequest($request);
//        if($form->isSubmitted() && $form->isValid())
//        {
//
//            $em = $this->getDoctrine()->getManager();
//            $user = $em->getRepository(User::class)->find($this->getUser()->getId());
//            if($encoder->isPasswordValid($user, $userinit->getTest()))
//            {
//                $newpassword = $encoder->encodePassword($user, $userinit->getPassword());
//                $user->setPassword($newpassword);
//                $em->persist($user);
//                $em->flush();
//                $this->addFlash('change', 'Votre mot de passe  a ete modifié, reconnectez vous!');
//                return $this->redirectToRoute('security_login');
//            }
//           else
//           {
//               $form->addError(new FormError('Ancien mot de passe incorrecte'));
//           }
//
//        }
//        $response =$this->render('security/security/changepassword.html.twig',[
//            'form' => $form->createView(),
//        ]);
//        $response->setSharedMaxAge(0);
//        $response->headers->addCacheControlDirective('no-cache', true);
//        $response->headers->addCacheControlDirective('no-store', true);
//        $response->headers->addCacheControlDirective('must-revalidate', true);
//        $response->setCache([
//            'max_age' => 0,
//            'private' => true,
//        ]);
//        return $response;
//
//    }
//    else
//    {
//        $this->addFlash('notice', 'Vous n\'avez pas le droit d\'accede a cette partie de l\'application');
//        return $this->redirectToRoute('security_login');
//    }
//    }
//    /**
//     * @Route("/forgottenPassword", name="security_forgotten_password")
//     */
//    public function forgotten(Request $request, TokenGeneratorInterface $tokenGenerator, \Swift_Mailer $mail)
//    {
//        if ($request->isMethod('POST')) {
//
//          $email = $request->request->get('_mail');
//
//          $em = $this->getDoctrine()->getManager();
//          $user = $em->getRepository(User::class)->findOneByEmail($email);
//          if($user === null)
//          {
//              $this->addFlash('notice', 'Adresse email inconnue');
//              return $this->redirectToRoute('security_forgotten_password');
//          }
//          $token = $tokenGenerator->generateToken();
//          $user->setResetToken($token);
//          $em->persist($user);
//          $em->flush();
//          $url = $this->generateUrl('security_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
//
//          $message = (new \Swift_Message('Réinitialisation mot de passe'))
//          ->setFrom('support@euclideservices.com')
//          ->setTo($user->getEmail())
//          ->setBody("Cliquez sur le lien suivant pour réinitialiser votre mot de passe ".$url, 'text/html');
//
//          $mail->send($message);
//          $this->addFlash('change', 'Un message a été envoyé à votre adresse email, veuillez consulter votre boite de réception');
//        }
//
//
//         $response = $this->render('security/security/forget.html.twig');
//
//         $response->setSharedMaxAge(0);
//         $response->headers->addCacheControlDirective('no-cache', true);
//         return $response;
//    }
//
//    /**
//     * @Route("/Users", name="security_users")
//     */
//    public function users(UserRepository $userRepository)
//    {
//         if($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))
//            {
//
//                $response = $this->render('security/security/users.html.twig', [
//                    'users' => $userRepository->findAll(),
//                ]);
//                $response->setSharedMaxAge(0);
//                $response->headers->addCacheControlDirective('no-cache', true);
//                $response->headers->addCacheControlDirective('no-store', true);
//                $response->headers->addCacheControlDirective('must-revalidate', true);
//                $response->setCache([
//                    'max_age' => 0,
//                    'private' => true,
//                ]);
//                return $response;
//            }
//            else
//            {
//                $this->addFlash('notice', 'Vous n\'avez pas le droit d\'accede a cette partie de l\'application');
//                return $this->redirectToRoute('security_login');
//            }
//
//    }
//
//    /**
//     * @Route("/User/{user}", name="security_user")
//     */
//    public function user(UserRepository $userRepository,User $user)
//    {
//     if($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))
//        {
//
//            $response = $this->render('security/security/user.html.twig', [
//                'user' => $user,
//            ]);
//            $response->setSharedMaxAge(0);
//            $response->headers->addCacheControlDirective('no-cache', true);
//            $response->headers->addCacheControlDirective('no-store', true);
//            $response->headers->addCacheControlDirective('must-revalidate', true);
//            $response->setCache([
//                'max_age' => 0,
//                'private' => true,
//            ]);
//            return $response;
//        }
//        else
//        {
//            $this->addFlash('notice', 'Vous n\'avez pas le droit d\'accede a cette partie de l\'application');
//            return $this->redirectToRoute('security_login');
//        }
//
//    }
//
//    /**
//     * @Route("/ResetPassword/{token}", name="security_reset_password")
//     */
//    public function reset(Request $request, string $token, UserPasswordEncoderInterface $encoder)
//    {
//        $userinit =  new User();
//        $form = $this->createForm(changePasswordType::class, $userinit);
//        $form->remove('test');
//        $form->handleRequest($request);
//        if($form->isSubmitted() && $form->isValid())
//        {
//            $em = $this->getDoctrine()->getManager();
//            $user = $em->getRepository(User::class)->findOneByResetToken($token);
//            if($user === null)
//            {
//                $this->addFlash('notice', 'Chaine de réinitialisation invalide');
//                return $this->redirectToRoute('security_login');
//            }
//            $user->setResetToken(null);
//            $newpassword = $encoder->encodePassword($user,$userinit->getPassword());
//            $user->setPassword($newpassword);
//            $em->persist($user);
//            $em->flush();
//            $this->addFlash('change', 'Reinitialisation reussie');
//            $response = $this->redirectToRoute('security_login');
//            $response->setSharedMaxAge(0);
//            $response->headers->addCacheControlDirective('no-cache', true);
//            $response->headers->addCacheControlDirective('no-store', true);
//            $response->headers->addCacheControlDirective('must-revalidate', true);
//            $response->setCache([
//                'max_age' => 0,
//                'private' => true,
//            ]);
//            return $response;
//        }
//
//
//        return $this->render('security/security/reset.html.twig', ['form' => $form->createView()]
//            );
//    }
//
//    /**
//     * @Route("/Activation/{token}", name="security_activation")
//     */
//    public function active(Request $request, string $token)
//    {
//
//            $em = $this->getDoctrine()->getManager();
//            $user = $em->getRepository(User::class)->findOneByResetToken($token);
//            if($user === null)
//            {
//                $this->addFlash('notice', 'Chaine d\'activation invalide');
//                return $this->redirectToRoute('security_login');
//            }
//            $user->setEnabled(true);
//            $em->persist($user);
//            $em->flush();
//            $this->addFlash('notice', 'Compte active, veuillez  definir votre mot de passe pour la premiere connexion');
//            return $this->redirectToRoute('security_reset_password', ['token' => $token]);
//
//
//        return $this->render('security/security/reset.html.twig', ['form' => $form->createView()]
//            );
//    }
//
//    /**
//     * @Route("/UserDisable", name="security_user_disable")
//     */
//    public function UserdisableAction(Request $request)
//    {
//        if($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))
//        {
//            $em = $this->getDoctrine()->getManager();
//            //$users = $em->getrepository(User::class)->findBy(array('agence' => $this->getUser()->getAgence()->getId()));
//            $user =  $em->getrepository(User::class)->find($request->get('usr'));
//            if(!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN' ) || $user->getFonction() != 'proprietaire')
//            {
//                $user->setEnabled(false);
//                $em->persist($user);
//            }
//            $em->flush();
//            $res['ok']= 'ok';
//            $response = new Response();
//            $response->headers->set('content-type','application/json');
//            $re = json_encode($res);
//            $response->setContent($re);
//            return $response;
//        }
//        else return $this->redirect($this->generateUrl('security_login'));
//    }
//
//    /**
//     * @Route("/UserEnable", name="security_user_enable")
//     */
//    public function UserenableAction(Request $request)
//    {
//
//        if($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))
//        {
//            $em = $this->getDoctrine()->getManager();
//            //$users = $em->getrepository(User::class)->findBy(array('agence' => $this->getUser()->getAgence()->getId()));
//            $user =  $em->getrepository(User::class)->find($request->get('usr'));
//            if(!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN' ) || $user->getFonction() != 'proprietaire')
//            {
//                $user->setEnabled(true);
//                $em->persist($user);
//            }
//            $em->flush();
//            $res['ok']= 'ok';
//            $response = new Response();
//            $response->headers->set('content-type','application/json');
//            $re = json_encode($res);
//            $response->setContent($re);
//            return $response;
//        }
//        else return $this->redirect($this->generateUrl('security_login'));
//    }
//
//     /**
//     * @Route("/edit_user/{user}", name="security_user_edit")
//     */
//    public function edit_user(Request $request, User $user)
//    {
//        if($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))
//        {
//
//
//
//
//        $form = $this->createForm(RegistrationType::class, $user);
//        $form->remove('username');
//
//        $form->handleRequest($request);
//
//        if($form->isSubmitted() && $form->isValid())
//        {
//
//            $this->getDoctrine()->getManager()->flush();
//            $this->addFlash('notice', 'modifié avec succès');
//            return $this->redirectToRoute('security_user', ['user' => $user->getId()]);
//
//        }
//        $response = $this->render('security/security/edit.html.twig',[
//            'form' => $form->createView(),
//        ]);
//        $response->setSharedMaxAge(0);
//        $response->headers->addCacheControlDirective('no-cache', true);
//        $response->headers->addCacheControlDirective('no-store', true);
//        $response->headers->addCacheControlDirective('must-revalidate', true);
//        $response->setCache([
//            'max_age' => 0,
//            'private' => true,
//        ]);
//        return $response;
//    }
//    else
//    {
//        $this->addFlash('notice', 'Vous n\'avez pas le droit d\'accede a cette partie de l\'application');
//        return $this->redirectToRoute('security_login');
//    }
//    }
}
