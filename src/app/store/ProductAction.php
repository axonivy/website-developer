<?php
namespace app\store;

use Psr\Container\ContainerInterface;
use Slim\Exception\NotFoundException;

class ProductAction
{
    protected $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args) {
        $key = $args['key'] ?? '';
        $product = Store::getProductByKey($key);
        if ($product == null) {
            throw new NotFoundException($request, $response);
        }
        
        $version = $args['version'] ?? '';
        if (empty($version)) {
            $version = $product->getLatestVersion();
        } else if (!$product->hasVersion($version)) {
           throw new NotFoundException($request, $response);
        }
        
        $mavenArtifacts = $product->getMavenArtifactsForVersion($version);
        
        $mavenArtifactsAsDependency = [];
        foreach ($mavenArtifacts as $artifact) {
            if ($artifact->getMakesSenseAsMavenDependency()) {
                $mavenArtifactsAsDependency[] = $artifact;
            }
        }
        
        $docArtifacts = [];
        foreach ($mavenArtifacts as $artifact) {
            if ($artifact->isDocumentation()) {
                $docArtifacts[] = $artifact;
            }
        }
        
        return $this->container->get('view')->render($response, 'app/store/product.html', [
            'product' => $product,
            'mavenArtifacts' => $mavenArtifacts,
            'mavenArtifactsAsDependency' => $mavenArtifactsAsDependency,
            'docArtifacts' => $docArtifacts,
            'selectedVersion' => $version
        ]);
    }
}
