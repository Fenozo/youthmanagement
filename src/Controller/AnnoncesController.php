<?php

namespace App\Controller;

use App\Entity\Images;
use App\Entity\Annonces;
use App\Form\AnnoncesType;
use App\Repository\AnnoncesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/annonces")
 */
class AnnoncesController extends AbstractController
{
    /**
     * @Route("/", name="annonces_index", methods={"GET"})
     */
    public function index(AnnoncesRepository $annoncesRepository): Response
    {


        $request = Request::createFromGlobals();
        $parPage = 10;

        $currentPage = $request->get('page', 1);



        $annonces = array();

        $offset = 1;

        if ($currentPage != null ) {
            $offset = $currentPage * $parPage  - $parPage ;
        }

        $datas = $annoncesRepository->getListAnnonces($parPage, $offset);

        $count = null;

        foreach($datas as $key => $annonce) {
            $annonces[$annonce['id']] = [
                'id'        => $annonce['id'],
                'title'     => $annonce['title'],
                'content'   => $annonce['content'],
            ];
            if ($annonce['cc'] && $count === null) {
                $count = $annonce['cc'];
            }
        }

        $paginate = [
            'nbpages'       => ceil($count/10),
            'currentPage'   => $currentPage,
        ];

        return $this->render('annonces/index.html.twig', [
            'annonces'  => [
                'datas'     => $annonces, 
                'count'     => $count,
                'paginate'  => $paginate 
            ],
            
        ]);
    }

    /**
     * @Route("/new", name="annonces_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $annonce = new Annonces();
        $form = $this->createForm(AnnoncesType::class, $annonce);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Recupere les images transmises
            $images = $form->get('images')->getData();

            if (!$annonce->getTitle()) {
                $this->addFlash('error', 'Title cannot be null');
                return $this->redirectToRoute('annonces_new');
            }
            if (!$annonce->getTitle()) {
                $this->addFlash('error', 'Description content cannot be null');
                return $this->redirectToRoute('annonces_new');
            }
            // On boucles sur les images
            foreach ($images as $image) {
                // On genere un nouveau nom de fichier
                $fichier = md5(uniqid()). '.'. $image->guessExtension();
                
                if (!in_array($image->guessExtension(), array('jpg','jpeg', 'png'))) {
                    $this->addFlash('error', 'File must be to have extension jpeg, jpg or png');
                    return $this->redirectToRoute('annonces_new');
                }

                // On copie le fichier dans le dossier uploads
                $image->move(
                    $this->getParameter('annonce_directory'),
                    $fichier
                );

                // On stocke l'image dans la base de donnees (son nom)
                $img = new Images();
                $img->setName($fichier);
                $annonce->addImage($img);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($annonce);
            $entityManager->flush();

            return $this->redirectToRoute('annonces_index');
        }

        return $this->render('annonces/new.html.twig', [
            'annonce' => $annonce,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="annonces_show", methods={"GET"})
     */
    public function show(Annonces $annonce): Response
    {
        $images = $annonce->getImages();
        $image_name = null;
        $exist = false;
        $annonce_folder = null;
        foreach($images as $key  => $image) {
            if ($key == 0 && !is_null( $image->getName())) {
                $image_name = $image->getName();
                $annonce_folder = '/uploads/annonces';
                $exist = true;
            } else {
                break;
            }
        }

        return $this->render('annonces/show.html.twig', [
            'annonce'   => $annonce,
            'image'     => [
                'exist'     => $exist,
                'name'      => $image_name,
                'folder'    => $annonce_folder,
            ]
        ]);
    }

    /**
     * @Route("/{id}/edit", name="annonces_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Annonces $annonce): Response
    {
        $form = $this->createForm(AnnoncesType::class, $annonce);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('annonces_index');
        }

        return $this->render('annonces/edit.html.twig', [
            'annonce' => $annonce,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="annonces_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Annonces $annonce): Response
    {
        if ($this->isCsrfTokenValid('delete'.$annonce->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($annonce);
            $entityManager->flush();
        }

        return $this->redirectToRoute('annonces_index');
    }
}
