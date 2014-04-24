<?php

namespace WebMeta\CommonBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use WebMeta\CommonBundle\Entity\Tournoi;
use WebMeta\CommonBundle\Entity\Invitation;
use WebMeta\CommonBundle\Entity\Rencontre;

use WebMeta\CommonBundle\Form\TournoiType;

class TournoiController extends Controller {

    public function tournoiAction() {
        $session = $this->get('session');
        $compte = $session->get('compte');
        $em = $this->getDoctrine()->getManager();
        $liste_tournoi = $em->getRepository('WebMetaCommonBundle:Tournoi')->findBy(array("id_compte" => $compte->getId()));


        return $this->render('WebMetaCommonBundle:Tournoi:tournoi.html.twig', array("liste_tournoi" => $liste_tournoi));
    }

    public function creationTournoiAction() {
        $tournoi = new Tournoi();
        $form = $this->createForm(new TournoiType(), $tournoi);

        return $this->render('WebMetaCommonBundle:Tournoi:creation.html.twig', array('form' => $form->createView()));
    }

    public function validationAction(Request $request) {
        //récupération de la session
        $session = $this->get('session');
        $compte = $session->get('compte');

        $tournoi = new Tournoi();

        $form = $this->createForm(new TournoiType(), $tournoi);

        $form->handleRequest($request);

        // Si le formulaire est valide alors on insert
        if ($form->isValid()) {
            //on attribue le tournoi au user et on affecte le statut en attente
            $tournoi->setIdCompte($compte->getId());
            $tournoi->setStatut("enAttente");

            //persistance des données en base
            $em = $this->getDoctrine()->getManager();
            $em->persist($tournoi);
            $em->flush();

            $message = "Votre tournoi a été crée avec succès";
        } else {
            $message = "Une erreur est survenue, création impossible";
        }

        // Message de confirmation pour l'utilisateur
        $this->get('session')->getFlashBag()->add(
                'notice', $message
        );

        return $this->redirect($this->generateUrl('tournoi_warbot'));
    }

    public function gestionTournoiAction($id, Request $request) {
        //session
        $session = $this->get('session');
        $compte = $session->get('compte');


        $t = $this->getDoctrine()
                ->getManager();
        $tournoi = $t->getRepository('WebMetaCommonBundle:Tournoi')
                ->findOneById($id);

        $inv = $this->getDoctrine()
                ->getManager();
        $renc = $this->getDoctrine()
                ->getManager();

        //tableau des invitations
        $liste_team = $inv->getRepository('WebMetaCommonBundle:Invitation')
                ->findBy(array('idTournoi' => $id, 'statut' => 'accepted'));

        //tableau des matchs
        $liste_match = $renc->getRepository('WebMetaCommonBundle:Rencontre')
                ->findByIdTournoi($id);



        //envoi d'invitation pour participer a un tournoi
        //formulaire d'ajout de rencontre
        $invitation = new Invitation();
        $invitation->setIdTournoi($tournoi->getId());


        $formInvitation = $this->createFormBuilder($invitation)
                ->add('idInvite', 'text', array('label' => 'nom de l\'equipe:'))
                ->add('valider', 'submit')
                ->getForm();


        //validation formulaire d'envoi de l'invitation
        $formInvitation->handleRequest($request);
        if ($formInvitation->isValid()) {
            //récupération de l'idée de l'équipe à inviter
            $nomE = $invitation->getIdInvite();
            $tmp = $t->getRepository('WebMetaCommonBundle:Equipe')
                    ->findOneByNom($nomE);
            $invitation->setIdInvite($tmp->getId());

            //configuration de la ligne a insérer en base
            $invitation->setStatut("enAttente");
            $invitation->setIdTournoi($id);
            $invitation->setIdCreateur($compte->getId());

            // persistance en bdd
            $em = $this->getDoctrine()->getManager();
            $em->persist($invitation);
            $em->flush();
        }



        return $this->render('WebMetaCommonBundle:Tournoi:gestion_tournoi.html.twig', array('liste_team' => $liste_team, 'liste_match' => $liste_match, 'tournoi' => $tournoi, 'formInvitation' => $formInvitation->createView()));

        /*
          //generation des tableaux de tournois
          for($i=1; $i <= $liste_team->length() ; $i++){
          $rencontre=new Rencontre();
          $rencontre->setIdequipe1($liste_team[$i])
          ->setIdequipe2($liste_team[$i+1])
          ->setIdTournoi($tournoi->getId())
          ->setDate(new \DateTime('today'));

          //persistance des données en base
          $em = $this->getDoctrine()->getManager();
          $em->persist($rencontre);
          $em->flush();
          }
         */
    }

}