<?php

namespace app\domain\doc;

class ExternalBook extends AbstractDocument
{

  public function __construct(string $name, string $rootPath, string $baseUrl, string $baseRessourceUrl, string $path)
  {
    parent::__construct($name, $rootPath, $baseUrl, $baseRessourceUrl, $path);
  }
}
