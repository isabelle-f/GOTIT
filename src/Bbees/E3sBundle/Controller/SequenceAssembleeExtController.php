<?php

namespace Bbees\E3sBundle\Controller;

use Bbees\E3sBundle\Entity\SequenceAssembleeExt;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\ArrayCollection;
use Bbees\E3sBundle\Services\GenericFunctionService;
use Bbees\E3sBundle\Entity\Voc;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Sequenceassembleeext controller.
 *
 * @Route("sequenceassembleeext")
 */
class SequenceAssembleeExtController extends Controller
{
    /**
     * Lists all sequenceAssembleeExt entities.
     *
     * @Route("/", name="sequenceassembleeext_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $sequenceAssembleeExts = $em->getRepository('BbeesE3sBundle:SequenceAssembleeExt')->findAll();

        return $this->render('sequenceassembleeext/index.html.twig', array(
            'sequenceAssembleeExts' => $sequenceAssembleeExts,
        ));
    }

    /**
     * Retourne au format json un ensemble de champs à afficher tab_collecte_toshow avec les critères suivant :  
     * a) 1 critère de recherche ($request->get('searchPhrase')) insensible à la casse appliqué à un champ (ex. codeCollecte)
     * b) le nombre de lignes à afficher ($request->get('rowCount'))
     * c) 1 critère de tri sur un collone  ($request->get('sort'))
     *
     * @Route("/indexjson", name="sequenceassembleeext_indexjson")
     * @Method("POST")
     */
    public function indexjsonAction(Request $request)
    {
       
        $em = $this->getDoctrine()->getManager();
        
        $rowCount = ($request->get('rowCount')  !== NULL) ? $request->get('rowCount') : 10;
        $orderBy = ($request->get('sort')  !== NULL) ? $request->get('sort') : array('sequenceAssembleeExt.id' => 'desc');  
        $minRecord = intval($request->get('current')-1)*$rowCount;
        $maxRecord = $rowCount; 
        // initialise la variable searchPhrase suivant les cas et définit la condition du where suivant les conditions sur le parametre d'url idFk
        $where = 'LOWER(sequenceAssembleeExt.codeSqcAssExt) LIKE :criteriaLower';
        $searchPhrase = $request->get('searchPhrase');
        if ( $request->get('searchPatern') !== null && $request->get('searchPatern') !== '' && $searchPhrase == '') {
            $searchPhrase = $request->get('searchPatern');
        }
        // Recherche de la liste des lots à montrer EstAligneEtTraite
        $tab_toshow =[];
        $toshow = $em->getRepository("BbeesE3sBundle:SequenceAssembleeExt")->createQueryBuilder('sequenceAssembleeExt')
            ->where($where)
            ->setParameter('criteriaLower', strtolower($searchPhrase).'%')
            ->leftJoin('BbeesE3sBundle:Voc', 'vocStatutSqcAss', 'WITH', 'sequenceAssembleeExt.statutSqcAssVocFk = vocStatutSqcAss.id')
            ->leftJoin('BbeesE3sBundle:Voc', 'vocGene', 'WITH', 'sequenceAssembleeExt.geneVocFk = vocGene.id')
            ->leftJoin('BbeesE3sBundle:Collecte', 'collecte', 'WITH', 'sequenceAssembleeExt.collecteFk = collecte.id')
            ->addOrderBy(array_keys($orderBy)[0], array_values($orderBy)[0])
            ->getQuery()
            ->getResult();
        $nb = count($toshow);
        $toshow = array_slice($toshow, $minRecord, $rowCount);  
        $lastTaxname = '';
        foreach($toshow as $entity)
        {
            $id = $entity->getId();
            $dateCreationSqcAssExt = ($entity->getdateCreationSqcAssExt() !== null) ?  $entity->getdateCreationSqcAssExt()->format('Y-m-d') : null;
            $DateMaj = ($entity->getDateMaj() !== null) ?  $entity->getDateMaj()->format('Y-m-d H:i:s') : null;
            $DateCre = ($entity->getDateCre() !== null) ?  $entity->getDateCre()->format('Y-m-d H:i:s') : null;       
            // récuparation du premier taxon identifié            
            $query = $em->createQuery('SELECT ei.id, ei.dateIdentification, rt.taxname as taxname, voc.code as codeIdentification FROM BbeesE3sBundle:EspeceIdentifiee ei JOIN ei.referentielTaxonFk rt JOIN ei.critereIdentificationVocFk voc WHERE ei.sequenceAssembleeExtFk = '.$id.' ORDER BY ei.id DESC')->getResult(); 
            $lastTaxname = ($query[0]['taxname'] !== NULL) ? $query[0]['taxname'] : NULL;
            $lastdateIdentification = ($query[0]['dateIdentification']  !== NULL) ? $query[0]['dateIdentification']->format('Y-m-d') : NULL; 
            $codeIdentification = ($query[0]['codeIdentification'] !== NULL) ? $query[0]['codeIdentification'] : NULL;
            //
            $tab_toshow[] = array("id" => $id, "sequenceAssembleeExt.id" => $id, 
             "sequenceAssembleeExt.codeSqcAssExtAlignement" => $entity->getCodeSqcAssExtAlignement(),
             "sequenceAssembleeExt.codeSqcAssExt" => $entity->getCodeSqcAssExt(),
             "sequenceAssembleeExt.accessionNumberSqcAssExt" => $entity->getAccessionNumberSqcAssExt(),
             "vocGene.code" => $entity->getGeneVocFk()->getCode(), 
             "vocStatutSqcAss.code" => $entity->getStatutSqcAssVocFk()->getCode(),                 
             "sequenceAssembleeExt.dateCreationSqcAssExt" => $dateCreationSqcAssExt,  
             "sequenceAssembleeExt.taxonOrigineSqcAssExt" => $entity->getTaxonOrigineSqcAssExt(),
             "collecte.codeCollecte" => $entity->getCollecteFk()->getCodeCollecte(),
             "lastTaxname" => $lastTaxname,   
             "lastdateIdentification" => $lastdateIdentification ,
             "codeIdentification" => $codeIdentification ,
             "sequenceAssembleeExt.dateCre" => $DateCre, "sequenceAssembleeExt.dateMaj" => $DateMaj,  );
        }    
 
        // Reponse Ajax
        $response = new Response ();
        $response->setContent ( json_encode ( array (
            "current"    => intval( $request->get('current') ), 
            "rowCount"  => $rowCount,            
            "rows"     => $tab_toshow, 
            "searchPhrase" => $searchPhrase,
            "total"    => $nb // total data array				
            ) ) );
        // Si il s’agit d’un SUBMIT via une requete Ajax : renvoie le contenu au format json
        $response->headers->set('Content-Type', 'application/json');

        return $response;          
    } 

    
    /**
     * Creates a new sequenceAssembleeExt entity.
     *
     * @Route("/new", name="sequenceassembleeext_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $sequenceAssembleeExt = new Sequenceassembleeext();
        $form = $this->createForm('Bbees\E3sBundle\Form\SequenceAssembleeExtType', $sequenceAssembleeExt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();            
            // on initialise le code sqcAlignement : setCodeSqcAssExtAlignement($codeSqcAlignement)
            $codeSqcAssExt = $this->createCodeSqcAssExt($sequenceAssembleeExt);
            $sequenceAssembleeExt->setCodeSqcAssExt($codeSqcAssExt);
            // on initialise le code sqcAlignement : setCodeSqcAssExtAlignement($codeSqcAlignement)
            $codeSqcAssExtAlignementExt = $this->createCodeSqcAssExtAlignement($sequenceAssembleeExt);
            $sequenceAssembleeExt->setCodeSqcAssExtAlignement($codeSqcAssExtAlignementExt);
            $em->persist($sequenceAssembleeExt);
            try {
                $em->flush();
            } 
            catch(\Doctrine\DBAL\DBALException $e) {
                $exception_message =  str_replace('"', '\"',str_replace("'", "\'", html_entity_decode(strval($e), ENT_QUOTES , 'UTF-8')));
                return $this->render('sequenceassembleeext/index.html.twig', array('exception_message' =>  explode("\n", $exception_message)[0]));
            }
            return $this->redirectToRoute('sequenceassembleeext_edit', array('id' => $sequenceAssembleeExt->getId(), 'valid' => 1));                     
        } 
        
            return $this->render('sequenceassembleeext/edit.html.twig', array(
                                'sequenceAssembleeExt' => $sequenceAssembleeExt,
                                'edit_form' => $form->createView(),
            )); 
    }

    /**
     * Finds and displays a sequenceAssembleeExt entity.
     *
     * @Route("/{id}", name="sequenceassembleeext_show")
     * @Method("GET")
     */
    public function showAction(SequenceAssembleeExt $sequenceAssembleeExt)
    {
        $deleteForm = $this->createDeleteForm($sequenceAssembleeExt);
        $editForm = $this->createForm('Bbees\E3sBundle\Form\SequenceAssembleeExtType', $sequenceAssembleeExt);

        return $this->render('show.html.twig', array(
            'sequenceAssembleeExt' => $sequenceAssembleeExt,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing sequenceAssembleeExt entity.
     *
     * @Route("/{id}/edit", name="sequenceassembleeext_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, SequenceAssembleeExt $sequenceAssembleeExt)
    {
        // recuperation du service generic_function_e3s
        $service = $this->get('bbees_e3s.generic_function_e3s'); 
        // recuperation  de l'Entity Mananger
        $em = $this->getDoctrine()->getManager();
                
        // memorisation des ArrayCollection        
        $especeIdentifiees = $service->setArrayCollectionEmbed('EspeceIdentifiees','EstIdentifiePars',$sequenceAssembleeExt);
        $sqcExtEstReferenceDanss = $service->setArrayCollection('SqcExtEstReferenceDanss',$sequenceAssembleeExt);
        $sqcExtEstRealisePars = $service->setArrayCollection('SqcExtEstRealisePars',$sequenceAssembleeExt);
       
        $deleteForm = $this->createDeleteForm($sequenceAssembleeExt);
        $editForm = $this->createForm('Bbees\E3sBundle\Form\SequenceAssembleeExtType', $sequenceAssembleeExt);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            // suppression des ArrayCollection 
            $service->DelArrayCollectionEmbed('EspeceIdentifiees','EstIdentifiePars',$sequenceAssembleeExt, $especeIdentifiees);
            $service->DelArrayCollection('SqcExtEstReferenceDanss',$sequenceAssembleeExt, $sqcExtEstReferenceDanss);
            $service->DelArrayCollection('SqcExtEstRealisePars',$sequenceAssembleeExt, $sqcExtEstRealisePars);
            //$em->persist($sequenceAssembleeExt); 
            // on initialise le code sqcAlignement : setCodeSqcAssExtAlignement($codeSqcAlignement)
            $codeSqcAssExtAlignementExt = $this->createCodeSqcAssExtAlignement($sequenceAssembleeExt);
            $sequenceAssembleeExt->setCodeSqcAssExtAlignement($codeSqcAssExtAlignementExt);
            $em->persist($sequenceAssembleeExt);
            // flush
            try {
                $em->flush();
            } 
            catch(\Doctrine\DBAL\DBALException $e) {
                $exception_message =  str_replace('"', '\"',str_replace("'", "\'", html_entity_decode(strval($e), ENT_QUOTES , 'UTF-8')));
                return $this->render('sequenceassembleeext/index.html.twig', array('exception_message' =>  explode("\n", $exception_message)[0]));
            } 
            $editForm = $this->createForm('Bbees\E3sBundle\Form\SequenceAssembleeExtType', $sequenceAssembleeExt);
            
           return $this->render('sequenceassembleeext/edit.html.twig', array(
                'sequenceAssembleeExt' => $sequenceAssembleeExt,
                'edit_form' => $editForm->createView(),
                'valid' => 1,
                ));
        }
        
        return $this->render('sequenceassembleeext/edit.html.twig', array(
            'sequenceAssembleeExt' => $sequenceAssembleeExt,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a sequenceAssembleeExt entity.
     *
     * @Route("/{id}", name="sequenceassembleeext_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, SequenceAssembleeExt $sequenceAssembleeExt)
    {
        $form = $this->createDeleteForm($sequenceAssembleeExt);
        $form->handleRequest($request);
        
        $submittedToken = $request->request->get('token');
        if (($form->isSubmitted() && $form->isValid()) || $this->isCsrfTokenValid('delete-item', $submittedToken) ) {
            $em = $this->getDoctrine()->getManager();
            try {
                $em->remove($sequenceAssembleeExt);
                $em->flush();
            } 
            catch(\Doctrine\DBAL\DBALException $e) {
                $exception_message =  str_replace('"', '\"',str_replace("'", "\'", html_entity_decode(strval($e), ENT_QUOTES , 'UTF-8')));
                return $this->render('sequenceassembleeext/index.html.twig', array('exception_message' =>  explode("\n", $exception_message)[0]));
            }   
        }

        return $this->redirectToRoute('sequenceassembleeext_index');
    }

    /**
     * Creates a form to delete a sequenceAssembleeExt entity.
     *
     * @param SequenceAssembleeExt $sequenceAssembleeExt The sequenceAssembleeExt entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(SequenceAssembleeExt $sequenceAssembleeExt)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('sequenceassembleeext_delete', array('id' => $sequenceAssembleeExt->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
    
    /**
     * Creates a createCodeSqcAssExt
     *
     * @param SequenceAssemblee $sequenceAssemblee The sequenceAssemblee entity
     *
     */
    private function createCodeSqcAssExt(SequenceAssembleeExt $sequenceAssembleeExt)
    {  
        $codeSqc = '';
        $em = $this->getDoctrine()->getManager();
        $EspeceIdentifiees =  $sequenceAssembleeExt->getEspeceIdentifiees();
        $nbEspeceIdentifiees = count($EspeceIdentifiees);
        if($nbEspeceIdentifiees > 0) {
            // Le statut de la sequence ET le referentiel Taxon = au derenier taxname attribué
            $codeStatutSqcAss = $sequenceAssembleeExt->getStatutSqcAssVocFk()->getCode();
            $arrayReferentielTaxon = array();
                foreach ($EspeceIdentifiees as $entityEspeceIdentifiees) {
                     $arrayReferentielTaxon[$entityEspeceIdentifiees->getReferentielTaxonFk()->getId()] = $entityEspeceIdentifiees->getReferentielTaxonFk()->getCodeTaxon();
                }
            ksort($arrayReferentielTaxon);
            reset($arrayReferentielTaxon);
            $firstTaxname = current($arrayReferentielTaxon);
            //var_dump($arrayReferentielTaxon); var_dump($firstTaxname);
            //$firstTaxname = array_slice($arrayReferentielTaxon, 0, 1)[0];
            $codeSqc = ($codeStatutSqcAss == 'VALIDEE') ? $firstTaxname : $codeStatutSqcAss.'_'.$firstTaxname;              
            // Le code de la collecte, le num_ind_biomol 
            $codeCollecte = $sequenceAssembleeExt->getCollecteFk()->getCodeCollecte();
            $numIndividuSqcAssExt = $sequenceAssembleeExt->getNumIndividuSqcAssExt();
            $accessionNumberSqcAssExt = $sequenceAssembleeExt->getAccessionNumberSqcAssExt();
            $codeOrigineSqcAssExt = $sequenceAssembleeExt->getOrigineSqcAssExtVocFk()->getCode();
            $codeSqc = $codeSqc.'_'.$codeCollecte.'_'.$numIndividuSqcAssExt.'_'.$accessionNumberSqcAssExt.'|'.$codeOrigineSqcAssExt;
        } else {
            $codeSqc = 0;
           //var_dump($nbEspeceIdentifiees);var_dump($codeSqc); exit; 
        }
        return $codeSqc;         
    }
    
    /**
     * Creates a createCodeSqcAssExtAlignement
     *
     * @param SequenceAssemblee $sequenceAssemblee The sequenceAssemblee entity
     *
     */
    private function createCodeSqcAssExtAlignement(SequenceAssembleeExt $sequenceAssembleeExt)
    {  
        $codeSqcAlignement = '';
        $em = $this->getDoctrine()->getManager();
        $EspeceIdentifiees =  $sequenceAssembleeExt->getEspeceIdentifiees();
        $nbEspeceIdentifiees = count($EspeceIdentifiees);
        if($nbEspeceIdentifiees>0) {
            // Le statut de la sequence ET le referentiel Taxon = au derenier taxname attribué
            $codeStatutSqcAss = $sequenceAssembleeExt->getStatutSqcAssVocFk()->getCode();
            $arrayReferentielTaxon = array();
            foreach ($EspeceIdentifiees as $entityEspeceIdentifiees) {
                 $arrayReferentielTaxon[$entityEspeceIdentifiees->getReferentielTaxonFk()->getId()] = $entityEspeceIdentifiees->getReferentielTaxonFk()->getCodeTaxon();
            }
            ksort($arrayReferentielTaxon);
            end($arrayReferentielTaxon);
            $lastCodeTaxon = current($arrayReferentielTaxon);
            $codeSqcAlignement = ($codeStatutSqcAss == 'VALIDEE') ? $lastCodeTaxon : $codeStatutSqcAss.'_'.$lastCodeTaxon;              
            // Le code de la collecte, le num_ind_biomol 
            $codeCollecte = $sequenceAssembleeExt->getCollecteFk()->getCodeCollecte();
            $numIndividuSqcAssExt = $sequenceAssembleeExt->getNumIndividuSqcAssExt();
            $accessionNumberSqcAssExt = $sequenceAssembleeExt->getAccessionNumberSqcAssExt();
            $codeOrigineSqcAssExt = $sequenceAssembleeExt->getOrigineSqcAssExtVocFk()->getCode();
            $codeSqcAlignement = $codeSqcAlignement.'_'.$codeCollecte.'_'.$numIndividuSqcAssExt.'_'.$accessionNumberSqcAssExt.'_'.$codeOrigineSqcAssExt;
        }   else {
            $codeSqcAlignement = 0;
        }
        return $codeSqcAlignement;                   
    }
}