<?php

/*
 * This file is part of the E3sBundle.
 *
 * Authors : see information concerning authors of GOTIT project in file AUTHORS.md
 *
 * E3sBundle is free software : you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * 
 * E3sBundle is distributed in the hope that it will be useful,but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with E3sBundle.  If not, see <https://www.gnu.org/licenses/>
 * 
 */

namespace Bbees\E3sBundle\Controller;

use Bbees\E3sBundle\Entity\Commune;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bbees\E3sBundle\Services\GenericFunctionService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Commune controller.
 *
 * @Route("commune")
 * @Security("has_role('ROLE_INVITED')")
 * @author Philippe Grison  <philippe.grison@mnhn.fr>
 */
class CommuneController extends Controller
{
    /**
     * Lists all commune entities.
     *
     * @Route("/", name="commune_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $communes = $em->getRepository('BbeesE3sBundle:Commune')->findAll();

        return $this->render('commune/index.html.twig', array(
            'communes' => $communes,
        ));
    }
    
     /**
     * Retourne au format json un ensemble de champs à afficher tab_station_toshow avec les critères suivant :  
     * a) 1 search criterion ($ request-> get ('searchPhrase')) insensitive to the case and  applied to a field
     * b) the number of lines to display ($ request-> get ('rowCount'))
     * c) 1 sort criterion on a collone ($ request-> get ('sort'))
     *
     * @Route("/indexjson", name="commune_indexjson")
     * @Method("POST")
     */
    public function indexjsonAction(Request $request)
    {   
        // load services
        $service = $this->get('bbees_e3s.generic_function_e3s');    
        $em = $this->getDoctrine()->getManager();
        //
        $rowCount = ($request->get('rowCount')  !== NULL) ? $request->get('rowCount') : 10;
        $orderBy = ($request->get('sort')  !== NULL) ? $request->get('sort') : array('commune.dateMaj' => 'desc', 'commune.id' => 'desc');  
        $minRecord = intval($request->get('current')-1)*$rowCount;
        $maxRecord = $rowCount; 
        // initializes the searchPhrase variable as appropriate and sets the condition according to the url idFk parameter
        $where = 'LOWER(commune.codeCommune) LIKE :criteriaLower';
        $searchPhrase = $request->get('searchPhrase');
        if ( $request->get('searchPatern') !== null && $request->get('searchPatern') !== '' && $searchPhrase == '') {
            $searchPhrase = $request->get('searchPatern');
        }
        // Search for the list to show
        $tab_toshow =[];
        $toshow = $em->getRepository("BbeesE3sBundle:Commune")->createQueryBuilder('commune')
            ->where($where)
            ->setParameter('criteriaLower', strtolower($searchPhrase).'%')
            ->leftJoin('BbeesE3sBundle:Pays', 'pays', 'WITH', 'commune.paysFk = pays.id')
            ->addOrderBy(array_keys($orderBy)[0], array_values($orderBy)[0])
            ->getQuery()
            ->getResult();
        $nb = count($toshow);
        $toshow = array_slice($toshow, $minRecord, $rowCount);          
        foreach($toshow as $entity)
        {
            $id = $entity->getId();
            $DateCre = ($entity->getDateCre() !== null) ?  $entity->getDateCre()->format('Y-m-d H:i:s') : null;
            $DateMaj = ($entity->getDateMaj() !== null) ?  $entity->getDateMaj()->format('Y-m-d H:i:s') : null;
            $tab_toshow[] = array("id" => $id, "commune.id" => $id, 
                "commune.codeCommune" => $entity->getCodeCommune(),
                "commune.nomCommune" => $entity->getNomCommune(),
                "commune.nomRegion" => $entity->getNomRegion(),
                "pays.codePays" => $entity->getPaysFk()->getCodePays(),
                "commune.dateCre" => $DateCre, "commune.dateMaj" => $DateMaj,
                "userCreId" => $service->GetUserCreId($entity), "commune.userCre" => $service->GetUserCreUsername($entity) ,"commune.userMaj" => $service->GetUserMajUsername($entity),
             );
        }                
        $response = new Response ();
        $response->setContent ( json_encode ( array (
            "current"    => intval( $request->get('current') ), 
            "rowCount"  => $rowCount,            
            "rows"     => $tab_toshow, 
            "total"    => $nb // total data array				
            ) ) );
        // If it is an Ajax request: returns the content in json format
        $response->headers->set('Content-Type', 'application/json');

        return $response;          
    }
    
    /**
     * Creates a new commune entity.
     *
     * @Route("/new", name="commune_new")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $commune = new Commune();
        $form = $this->createForm('Bbees\E3sBundle\Form\CommuneType', $commune);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {           
            $em = $this->getDoctrine()->getManager();
            $em->persist($commune);
            try {
                $flush = $em->flush();
                } 
            catch(\Doctrine\DBAL\DBALException $e) {
                $exception_message =  str_replace('"', '\"',str_replace("'", "\'", html_entity_decode(strval($e), ENT_QUOTES , 'UTF-8')));
                return $this->render('commune/index.html.twig', array('exception_message' =>  explode("\n", $exception_message)[0]));
            }  
            return $this->redirectToRoute('commune_edit', array('id' => $commune->getId(), 'valid' => 1));              
        }

        return $this->render('commune/edit.html.twig', array(
            'commune' => $commune,
            'edit_form' => $form->createView(),
        ));
    }

    /**
     * Creates a new commune entity for modal windows
     *
     * @Route("/newmodal", name="commune_newmodal")
     * @Method({"GET", "POST"})
     */
    public function newmodalAction(Request $request, $id_pays = null)
    {
        $commune = new Commune();
        $form = $this->createForm('Bbees\E3sBundle\Form\CommuneType', $commune, array('id_pays' => $id_pays,));
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // flush des données du formulaire
            $em = $this->getDoctrine()->getManager();
            $em->persist($commune);
            
            try {
                $flush = $em->flush();
                // mémorize the id and the name of Municipality created
                $select_id = $commune->getId();
                $select_name = $commune->getCodeCommune();
                // load a new empty Municipality entity
                $commune_new = new Commune();
                $form = $this->createForm('Bbees\E3sBundle\Form\CommuneType',$commune_new, array('id_pays' => $id_pays,));           
                // returns an empty form and the parameters of the new record created
                $response = new Response ();
                $response->setContent ( json_encode ( array (
                    'html_form' => $this->render('modal.html.twig', array('entityname' => 'commune', 'form' => $form->createView()))->getContent(),
                    'select_id' => $select_id,
                    'select_name' => $select_name,
                    'exception_message' => "",
                    'entityname' => 'personne',
                    ) ) );	
                } 
            catch(\Doctrine\DBAL\DBALException $e) {
                $exception_message = strval($e);
                // load a new empty Municipality entity
                $commune_new = new Commune();
                $form = $this->createForm('Bbees\E3sBundle\Form\CommuneType',$commune_new, array('id_pays' => $id_pays,));   
                // returns a form with the error message
                $response = new Response ();
                $response->setContent ( json_encode ( array (
                    'html_form' => $this->render('modal.html.twig', array('entityname' => 'commune', 'form' => $form->createView()))->getContent(),
                    'select_id' => 0,
                    'select_name' => "",
                    'exception_message' => $exception_message,
                    'entityname' => 'personne',
                    ) ) );	
                }   
            If ($request->isXmlHttpRequest()){
                // If it is an Ajax request: returns the content in json format
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            } else {
                var_dump("call to the function newmodalAction of controller CommuneController is not like a XmlHttpRequest"); exit;
            }
        }

        return $this->render('modal.html.twig', array(
            'entityname' => 'commune',
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a commune entity.
     *
     * @Route("/{id}", name="commune_show")
     * @Method("GET")
     */
    public function showAction(Commune $commune)
    {
        $deleteForm = $this->createDeleteForm($commune);
        
        $editForm = $this->createForm('Bbees\E3sBundle\Form\CommuneType', $commune);
        return $this->render('show.html.twig', array(
            'commune' => $commune,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));

    }

    /**
     * Displays a form to edit an existing commune entity.
     *
     * @Route("/{id}/edit", name="commune_edit")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function editAction(Request $request, Commune $commune)
    {
        $deleteForm = $this->createDeleteForm($commune);
        $editForm = $this->createForm('Bbees\E3sBundle\Form\CommuneType', $commune);
        $editForm->handleRequest($request);
        
        if ($editForm->isSubmitted() && $editForm->isValid()) {            
            $em = $this->getDoctrine()->getManager();
            try {
                $em->flush();
            } 
            catch(\Doctrine\DBAL\DBALException $e) {
                $exception_message =  str_replace('"', '\"',str_replace("'", "\'", html_entity_decode(strval($e), ENT_QUOTES , 'UTF-8')));
                return $this->render('commune/index.html.twig', array('exception_message' =>  explode("\n", $exception_message)[0]));
            } 
            return $this->render('commune/edit.html.twig', array(
            'commune' => $commune,
            'edit_form' => $editForm->createView(),
            'valid' => 1,
            ));
        }

        return $this->render('commune/edit.html.twig', array(
            'commune' => $commune,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a commune entity.
     *
     * @Route("/{id}", name="commune_delete")
     * @Method("DELETE")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function deleteAction(Request $request, Commune $commune)
    {
        $form = $this->createDeleteForm($commune);
        $form->handleRequest($request);
       
        $submittedToken = $request->request->get('token');
        if (($form->isSubmitted() && $form->isValid()) || $this->isCsrfTokenValid('delete-item', $submittedToken) ) {
            $em = $this->getDoctrine()->getManager();
            try {
                $em->remove($commune);
                $em->flush();
            } 
            catch(\Doctrine\DBAL\DBALException $e) {
                $exception_message =  str_replace('"', '\"',str_replace("'", "\'", html_entity_decode(strval($e), ENT_QUOTES , 'UTF-8')));
                return $this->render('commune/index.html.twig', array('exception_message' =>  explode("\n", $exception_message)[0]));
            } 
        }

        return $this->redirectToRoute('commune_index');
    }

    /**
     * Creates a form to delete a commune entity.
     *
     * @param Commune $commune The commune entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Commune $commune)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('commune_delete', array('id' => $commune->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
