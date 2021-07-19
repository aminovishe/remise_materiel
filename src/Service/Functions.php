<?php

namespace App\Service;

use App\Entity\Alert;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Functions
{
    protected $em;
    public $router;

    public function __construct(EntityManagerInterface $entityManager,     UrlGeneratorInterface $router)
    {
        $this->em = $entityManager;
        $this->router = $router;
    }

    public function transformXmlResponseToArray($xmlResponse, string $type = 'QueryList')
    {
        $xmlstr = $xmlResponse->getContent();
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xmlstr);

        if ($domDocument->getElementsByTagName('LoginResponse')[0]->attributes[0]->nodeValue === "false"){
            $alert = new Alert();
            $alert->setMessage("Merci de mettre à jour le mot de passe Infor XA Power-Link");
            $alert->setPath($this->router->generate('update_password_powerlink'));
            $this->em->persist($alert);
            $this->em->flush();

            return null;
        }

        $tagName = $type . 'Response';
        if ($domDocument->getElementsByTagName($tagName)[0]->attributes[2]->nodeValue === "true") {
            $DomainEntityElements = $domDocument->getElementsByTagName('DomainEntity');
            $returnedArray = [];
            foreach ($DomainEntityElements as $domainEntityElement) {
                $oneOrder = [];
                foreach ($domainEntityElement->childNodes as $domainEntityChilds) {
                    if ($domainEntityChilds->nodeName === "Property") {
                        $key = $domainEntityChilds->attributes['path']->nodeValue;
                        $value = $domainEntityChilds->childNodes['Value']->nodeValue;
                        array_push($oneOrder, [
                            'key' => $key,
                            'value' => $value,
                        ]);
                    }
                }
                array_push($returnedArray, $oneOrder);
            }
        } else {
            $returnedArray = null;
        }

        return $returnedArray;
    }

    public function validatePasswordStrength(string $password):array
    {
        /*
         * Password must be at least 8 characters in length.
         * Password must include at least one letter.
         * Password must include at least one number.
         * Password must include at least one special character.
         */
        $result = ['validate' => true, 'message' => null];

        $lettre = preg_match('@[a-zA-Z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);

        if(!$lettre || !$number || !$specialChars || strlen($password) < 8) {
            $result['validate'] = false;
            $result['message'] = "<b>Le mot de passe doit contenir :</b><ul><li>Au moins 8 caractères</li><li>Au moins une lettre</li><li>Au moins un chiffre</li><li>Au moins un caractère spécial</li></ul>";
        }

        return $result;
    }

    public function excelCellName($i)
    {
        $entiere = floor($i / 26);
        $reste = $i % 26;
        if($entiere > 0){
            return chr($entiere + 64).chr($reste + 65);
        }
        else{
            return chr($reste + 65);
        }
    }

    public function searchIn3DimArraySicamAPI($elem, $value, $searchedElem, $array) {
        foreach ($array as $key => $val) {
            $referenceKey = array_search($elem, array_column($val, 'key'));
            if ($val[$referenceKey]['value'] === $value){
                $searchedKey = array_search($searchedElem, array_column($val, 'key'));
                return $val[$searchedKey]['value'];
                break;
            }
        }
        return null;
    }
}
