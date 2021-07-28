<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Entity\Supplier;
use App\Service\Functions;
use App\Service\UpdateAppParams;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HomeController extends AbstractController
{
    private $client;
    protected $em;
    protected $updateAppParams;
    protected $functions;

    public function __construct(
        EntityManagerInterface $entityManager,
        HttpClientInterface $client,
        Functions $functions,
        UpdateAppParams $updateAppParams
    )
    {
        $this->em = $entityManager;
        $this->client = $client;
        $this->functions = $functions;
        $this->updateAppParams = $updateAppParams;
    }

    /**
     * @Route("/", name="home")
     */
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    /**
     * @Route("/generate_remise_materiel/{numOF}", defaults={"numOF" = null}, name="generate_remise_materiel")
     */
    public function generateRemiseMateriel($numOF = null): Response
    {
        $powerlinkParams = $this->updateAppParams->getParamByKey('powerlink');
        $lastRMST = (int)$this->updateAppParams->getParamByKey('lastRMST');

        if ($numOF === null){
            return $this->render('print/remise_materiel.html.twig',[
                'numRMST' => $lastRMST,
            ]);
        }

        $numOF = strtoupper($numOF);

        $response = $this->client->request(
            'GET',
            "http://sicame.sicame.com:36001/SystemLink/servlet/SystemLinkServlet?SystemLinkRequest=" . "<?xml version='1.0' encoding='UTF-8'?><!DOCTYPE System-Link SYSTEM 'SystemLinkRequest.dtd'><System-Link><Login userId='". $powerlinkParams['userId'] ."' password='". $powerlinkParams['password'] ."' maxIdle='900000' properties='com.pjx.cas.domain.EnvironmentId=II, com.pjx.cas.domain.SystemName=SICAME.SICAME.COM, com.pjx.cas.user.LanguageId=fr'/><Request sessionHandle='*current' workHandle='*new' broker='EJB' maxIdle='1000'><QueryList name='queryListComposantOF_remisemat' domainClass='com.mapics.obpm.MoComponent' includeMetaData='true' maxReturned='-1'><Pql><![CDATA[SELECT relatedManufacturingOrder.relatedItem.item,componentItem,componentItemDescription,relatedItemWarehouse.primaryVendor,adjustedQuantityPerExpanded,order,relatedManufacturingOrder.totalOrderQuantity REQUIRED relatedManufacturingOrder,relatedItemWarehouse,relatedManufacturingOrder.relatedItem ORDER BY order,componentItem,componentWarehouse,userSequence,sequenceNumber]]></Pql><Constraint domainClass='com.mapics.obpm.ManufacturingOrder' relationshipName='relatedMoMaterials'><Key><Property path='order'><Value><![CDATA[". $numOF ."]]></Value></Property></Key></Constraint></QueryList></Request></System-Link>", [
                'headers' => ['Content-Type' => 'application/xml']
            ]
        );
        $responseArray = $this->functions->transformXmlResponseToArray($response);

        if ($responseArray === null){
            $this->addFlash('error', 'Une erreur est survenue.<br><b>Probablement vous devez changer le Mot de Passe Power-Link</b>');
            return $this->redirectToRoute('home');
        }

        $columnsKeys = count($responseArray) > 0 ? $responseArray[0] : null;

        if ($columnsKeys) {
            //----- Keys ----
            $produitFiniRefKEY = array_search('relatedManufacturingOrder.relatedItem.item', array_column($columnsKeys, 'key'));
            $composantRefKEY = array_search('componentItem', array_column($columnsKeys, 'key'));
            $composantDescriptionKEY = array_search('componentItemDescription', array_column($columnsKeys, 'key'));
            $supplierRefKEY = array_search('relatedItemWarehouse.primaryVendor', array_column($columnsKeys, 'key'));
            $quantityPerUnitKEY = array_search('adjustedQuantityPerExpanded', array_column($columnsKeys, 'key'));
            $produitFiniQuantityKEY = array_search('relatedManufacturingOrder.totalOrderQuantity', array_column($columnsKeys, 'key'));
            //----- Keys ----
        }

        $numRMST = $lastRMST;
        $datas = [];
        $supplierRef = null;
        foreach ($responseArray as $line){
            $composantRef = $line[$composantRefKEY]['value'];
            $datas[$composantRef] = [
                'composantRef' => $composantRef,
                'description' => $line[$composantDescriptionKEY]['value'],
                'produitFini' => $line[$produitFiniRefKEY]['value'],
                'is_st_ref' => substr($composantRef, -2,2) == "ST",
                'supplier' => $line[$supplierRefKEY]['value'],
                'quantityPerUnit' => (float)$line[$quantityPerUnitKEY]['value'],
                'produitFiniQuantity' => (float)$line[$produitFiniQuantityKEY]['value'],
            ];
            if (substr($composantRef, -2,2) == "ST"){
                $supplierRef = $datas[$composantRef]['supplier'];
            }
        }

        if ($supplierRef){
            $supplier = $this->em->getRepository(Supplier::class)->findBy(['ref' => $supplierRef]);
            if (!is_object($supplier)){
                $supplier = [];
                $response = $this->client->request(
                    'GET',
                    "http://sicame.sicame.com:36001/SystemLink/servlet/SystemLinkServlet?SystemLinkRequest=" . "<?xml version='1.0' encoding='UTF-8'?><!DOCTYPE System-Link SYSTEM 'SystemLinkRequest.dtd'><System-Link><Login userId='". $powerlinkParams['userId'] ."' password='". $powerlinkParams['password'] ."' maxIdle='900000' properties='com.pjx.cas.domain.EnvironmentId=II, com.pjx.cas.domain.SystemName=SICAME.SICAME.COM, com.pjx.cas.user.LanguageId=fr'/><Request sessionHandle='*current' workHandle='*new' broker='EJB' maxIdle='1000'><QueryList name='queryListFournisseur_remisemat' domainClass='com.mapics.pm.Vendor' includeMetaData='true' maxReturned='1'><Pql><![CDATA[SELECT vendor,name,address1,address4City,postalCode,country WHERE (vendor = '". $supplierRef ."')]]></Pql></QueryList></Request></System-Link>", [
                        'headers' => ['Content-Type' => 'application/xml']
                    ]
                );
                $responseArray = $this->functions->transformXmlResponseToArray($response);

                $columnsKeys = count($responseArray) > 0 ? $responseArray[0] : null;

                if ($columnsKeys) {
                    $supplier = [
                        'ref' => $responseArray[0][array_search('vendor', array_column($columnsKeys, 'key'))]['value'],
                        'nom' => $responseArray[0][array_search('name', array_column($columnsKeys, 'key'))]['value'],
                        'address' => $responseArray[0][array_search('address1', array_column($columnsKeys, 'key'))]['value'],
                        'postalCode' => $responseArray[0][array_search('postalCode', array_column($columnsKeys, 'key'))]['value'],
                        'city' => $responseArray[0][array_search('address4City', array_column($columnsKeys, 'key'))]['value'],
                        'country' => $responseArray[0][array_search('country', array_column($columnsKeys, 'key'))]['value'],
                    ];
                    if ($supplier['country']){
                        $response = $this->client->request(
                            'GET',
                            "http://sicame.sicame.com:36001/SystemLink/servlet/SystemLinkServlet?SystemLinkRequest=" . "<?xml version='1.0' encoding='UTF-8'?><!DOCTYPE System-Link SYSTEM 'SystemLinkRequest.dtd'><System-Link><Login userId='". $powerlinkParams['userId'] ."' password='". $powerlinkParams['password'] ."' maxIdle='900000' properties='com.pjx.cas.domain.EnvironmentId=II, com.pjx.cas.domain.SystemName=SICAME.SICAME.COM, com.pjx.cas.user.LanguageId=fr'/><Request sessionHandle='*current' workHandle='*new' broker='EJB' maxIdle='1000'><QueryObject name='queryObject_Pays_Valeurpardéfaut' domainClass='com.mapics.cbo.CountryCodeFile' includeMetaData='true'><Pql><![CDATA[SELECT name WHERE country = '". $supplier['country'] ."']]></Pql></QueryObject></Request></System-Link>", [
                                'headers' => ['Content-Type' => 'application/xml']
                            ]
                        );
                        $responseArray = $this->functions->transformXmlResponseToArray($response, 'QueryObject');
                        $columnsKeys = count($responseArray) > 0 ? $responseArray[0] : null;
                        $supplier['country'] = $responseArray[0][array_search('name', array_column($columnsKeys, 'key'))]['value'];
                    }
                }
            }
        }

        return $this->render('print/remise_materiel.html.twig',[
            'numOf' => $numOF,
            'datas' => $datas,
            'supplier' => $supplier,
            'numRMST' => $numRMST,
        ]);
    }

    /**
     * @Route("/increment_num_RMST", name="increment_num_RMST")
     */
    public function incrementNumRMST(Request $request)
    {
        $lastRMST = (int)$this->updateAppParams->getParamByKey('lastRMST');
        $this->updateAppParams->setParamByKey('lastRMST', $lastRMST + 1);

        return new JsonResponse(
            array(
                'status' => 200
            )
        );
    }
}
