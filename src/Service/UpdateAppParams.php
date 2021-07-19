<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Yaml\Yaml;

class UpdateAppParams
{
    protected $em;
    protected $appParamsDirectory;

    public function __construct(EntityManagerInterface $entityManager, $appParamsDirectory)
    {
        $this->em = $entityManager;
        $this->appParamsDirectory = $appParamsDirectory;
    }

    protected function getParamsDirectory()
    {
        return $this->appParamsDirectory;
    }

    public function setParamByKey(string $key, $value)
    {
        $appParams = Yaml::parseFile($this->getParamsDirectory());
        $appParams[$key] = $value;
        $appParamsYML = Yaml::dump($appParams);
        file_put_contents($this->getParamsDirectory(), $appParamsYML);
    }

    public function getParamByKey(string $key)
    {
        $appParams = Yaml::parseFile($this->getParamsDirectory());
        return $appParams[$key];
    }
}
