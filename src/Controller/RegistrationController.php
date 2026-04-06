<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Generate 6-digit OTP
            $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->setOtpCode($otp);
            $user->setIsVerified(false);

            $entityManager->persist($user);
            $entityManager->flush();

            // Store email in session to identify user during OTP verification
            $request->getSession()->set('verify_email', $user->getEmail());

            // Send OTP via Email
            $emailSent = false;
            try {
                $emailMsg = (new Email())
                    ->from('smitbambharoliya76@gmail.com')
                    ->to($user->getEmail())
                    ->subject('Your ServiceHub Verification Code')
                    ->html($this->renderView('registration/otp_email.html.twig', [
                        'otp' => $otp,
                        'name' => $user->getFullName(),
                    ]));

                $mailer->send($emailMsg);
                $emailSent = true;
                $this->addFlash('success', 'Registration successful! OTP sent to ' . $user->getEmail() . '. Please check your inbox (and spam folder).');
            } catch (\Exception $e) {
                // Dev mode: show exact error + OTP fallback
                $this->addFlash('error', '⚠️ Email Error: ' . $e->getMessage());
                $this->addFlash('warning', '🔑 Dev Fallback OTP: <strong style="font-size:1.5rem;letter-spacing:4px;">' . $otp . '</strong><br><small>Use this code to verify your account</small>');
            }

            return $this->redirectToRoute('app_verify_otp');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
