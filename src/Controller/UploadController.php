<?php

namespace App\Controller;

use App\Form\FacturationType;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class UploadController extends AbstractController
{

    #[Route('/upload', name: 'upload', methods: ['GET', 'POST'])]
    public function upload(Request $request, KernelInterface $kernel): Response
    {
        $form = $this->createForm(FacturationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('facturationFile')->getData();

            $app = new Application($kernel);
            $app->setAutoExit(false);
            $input = new ArrayInput([
                'command' => 'app:parse-facturation',
                'filePath' => $file->getRealPath()
            ]);

            $output = new NullOutput();

            try {
                $app->run($input, $output); // todo in background ?
            } catch (\Throwable $t) {
                return $this->redirectToRoute('failure', ['error' => $t->getMessage()]);
            }

            return $this->redirectToRoute('success');
        }

        return $this->render('upload/index.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/success', name: 'success', methods: ['GET'])]
    public function success(): Response
    {
        return $this->render('upload/success.html.twig');
    }
}