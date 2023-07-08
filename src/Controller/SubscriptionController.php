<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Form\SubscriptionType;
use App\Repository\SubscriptionRepository;
use App\Services\OkkazeoCrawler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class SubscriptionController extends AbstractController
{
    #[Route('/', name: 'app_subscription')]
    public function index(): Response
    {
        // Todo : use router
        return new RedirectResponse('/subscriptions/new');
    }

    #[Route('subscriptions/new', name: 'new_subscription')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SubscriptionType::class, new Subscription());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $subscription = $form->getData();
            $entityManager->persist($subscription);
            $entityManager->flush();
            $this->addFlash('info', 'L\'abonnement a été créé avec succès!');

            return $this->redirectToRoute('edit_subscription', ['key' => $subscription->getKey()]);
        }

        return $this->render('subscription/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('subscriptions/edit/{key}', name: 'edit_subscription')]
    public function edit($key, SubscriptionRepository $subscriptionRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $subscription = $subscriptionRepository->findOneBy(['key' => $key]);
        $form = $this->createForm(SubscriptionType::class, $subscription);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('info', 'Modifications enregistrées!');
        }

        return $this->render('subscription/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('email_tester/{key}', name: 'email_tester')]
    public function email_tester($key, SubscriptionRepository $subscriptionRepository, OkkazeoCrawler $crawler): Response
    {
        set_time_limit(90);

        $subscription = $subscriptionRepository->findOneBy(['key' => $key]);

        $okkazeoAnnonces = $crawler->getOkkazeoAnnonces("https://www.okkazeo.com/jeux/arrivages?FiltreCodePostal={$subscription->getFilterZipcode()}&FiltreDistance={$subscription->getFilterRange()}&FiltreRechJeux=on&FiltreLangue=1&FiltreRechPrixMin=&FiltreRechPrixMax=&page=1");

        $topRankedAnnonces = [];
        $subscription->setLastAnnonceUrl(null);
        $crawler->filterAnnonces($okkazeoAnnonces, $subscription, $topRankedAnnonces);
        $crawler->sortAnnonces($topRankedAnnonces);

        return $this->render(
            'emails/notification.html.twig',
            [
                'subscription' => $subscription,
                'topRankedAnnonces' => $topRankedAnnonces,
            ]
        );
    }

    #[Route('email_sender/{key}', name: 'email_sender')]
    public function email_sender($key, SubscriptionRepository $subscriptionRepository, OkkazeoCrawler $crawler, MailerInterface $mailer): Response
    {
        set_time_limit(90);

        $subscription = $subscriptionRepository->findOneBy(['key' => $key]);

        $okkazeoAnnonces = $crawler->getOkkazeoAnnonces("https://www.okkazeo.com/jeux/arrivages?FiltreCodePostal={$subscription->getFilterZipcode()}&FiltreDistance={$subscription->getFilterRange()}&FiltreRechJeux=on&FiltreLangue=1&FiltreRechPrixMin=&FiltreRechPrixMax=&page=1");

        $topRankedAnnonces = [];
        $subscription->setLastAnnonceUrl(null);
        $crawler->filterAnnonces($okkazeoAnnonces, $subscription, $topRankedAnnonces);
        $crawler->sortAnnonces($topRankedAnnonces);

        $email = (new TemplatedEmail())
            ->from('mailer@wink-dev.com')
            ->to($subscription->getEmail())
            ->subject('Okkazeo Alert')
            ->htmlTemplate('emails/notification.html.twig')

            // pass variables (name => value) to the template
            ->context([
                'subscription' => $subscription,
                'topRankedAnnonces' => $topRankedAnnonces,
            ]);

        $mailer->send($email);

        return new Response('OK');
    }

    #[Route('subscriptions/unsubscribe/{key}', name: 'remove_subscription')]
    public function remove($key, SubscriptionRepository $subscriptionRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $subscription = $subscriptionRepository->findOneBy(['key' => $key]);

        if ($subscription) {
            $entityManager->remove($subscription);
            $entityManager->flush();
            $this->addFlash('info', 'Unsubscribed');
        }

        return $this->redirectToRoute('new_subscription');
    }
}
