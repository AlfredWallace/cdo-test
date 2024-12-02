<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UploadController extends AbstractController
{

    #[Route('/upload', name: 'upload')]
    public function upload(): Response
    {
        return $this->render('upload/index.html.twig');
    }
}