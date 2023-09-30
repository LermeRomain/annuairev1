<?php

namespace App\Controller;

use App\Entity\Contacts;
use App\Entity\Messages;
use App\Form\ContactsType;
use App\Repository\ContactsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Mpdf\Mpdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/contacts')]
class ContactsController extends AbstractController
{
    #[Route('/', name: 'app_contacts_index', methods: ['GET'])]
    public function index(ContactsRepository $contactsRepository): Response
    {
        return $this->render('contacts/index.html.twig', [
            'contacts' => $contactsRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_contacts_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contact = new Contacts();
        $form = $this->createForm(ContactsType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contact);
            $entityManager->flush();

            return $this->redirectToRoute('app_contacts_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contacts/new.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contacts_show', methods: ['GET'])]
    public function show(Contacts $contact): Response
    {
        return $this->render('contacts/show.html.twig', [
            'contact' => $contact,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contacts_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contacts $contact, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContactsType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_contacts_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contacts/edit.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contacts_delete', methods: ['POST'])]
    public function delete(Request $request, Contacts $contact, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contact->getId(), $request->request->get('_token'))) {
            $entityManager->remove($contact);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_contacts_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/contacts/{id}/pdf', name: 'app_contact_pdf', methods: ['GET','POST'])]
    public function generatePdf(Request $request, Contacts $contact, Messages $messages): Response
    {
        $mpdf = new \Mpdf\Mpdf();

        // Générez le contenu HTML de la page
        $htmlContent = $this->renderView('contacts/pdf_template.html.twig', ['contact' => $contact]);

        // Ajoutez du contenu
        $mpdf->WriteHTML($htmlContent);

        // Récupérez les messages du contact
        $messages = $contact->getMessages();

        // Générez le contenu HTML pour les messages
        $messagesHtml = $this->renderView('contacts/pdf_messages_template.html.twig', ['messages' => $messages]);

        // Ajoutez le contenu HTML des messages
        $mpdf->WriteHTML($messagesHtml);

        // Générez le fichier PDF
        $mpdf->Output();

        exit;
    }
}
