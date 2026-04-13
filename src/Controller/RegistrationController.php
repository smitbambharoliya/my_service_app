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
            // Manually handle the role from the unmapped dropdown
            $selectedRole = $form->get('roles')->getData();
            $user->setRoles([$selectedRole]);

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Generate 6-digit OTP
            $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->setOtpCode($otp);
            $expiryMinutes = (int)($_ENV['OTP_EXPIRY_MINUTES'] ?? 10);
            $user->setOtpExpiresAt(new \DateTimeImmutable("+ {$expiryMinutes} minutes"));
            $user->setIsVerified(false);

            $entityManager->persist($user);
            $entityManager->flush();

            // Store email in session to identify user during OTP verification
            $request->getSession()->set('verify_email', $user->getEmail());

            // Send OTP via Email
            $emailSent = false;
            try {
                $emailMsg = (new Email())
                    ->from($_ENV['MAILER_FROM'] ?? 'noreply@servicehub.local')
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
                // Determine environment directly if desired or just wrap based on $_ENV['APP_ENV']
                if (($_ENV['APP_ENV'] ?? 'prod') === 'dev') {
                    $this->addFlash('error', '⚠️ Email Error: ' . $e->getMessage());
                    $this->addFlash('warning', '🔑 Dev Fallback OTP: <strong style="font-size:1.5rem;letter-spacing:4px;">' . $otp . '</strong><br><small>Use this code to verify your account</small>');
                } else {
                    $this->addFlash('error', 'There was a problem sending the verification email. Please try again.');
                }
            }

            return $this->redirectToRoute('app_verify_otp');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
