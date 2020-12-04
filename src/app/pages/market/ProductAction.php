<?php
namespace app\pages\market;

use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;
use app\domain\market\Market;
use app\domain\market\Product;
use Slim\Psr7\Request;

class ProductAction
{

    private Twig $view;
    
    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    public function __invoke($request, $response, $args) {
        $key = $args['key'] ?? '';
        $product = Market::getProductByKey($key);
        if ($product == null) {
            throw new HttpNotFoundException($request);
        }
        
        $mavenProductInfo = $product->getMavenProductInfo();
        $version = $args['version'] ?? '';
        $mavenArtifactsAsDependency = [];
        $mavenArtifacts = [];
        $docArtifacts = [];
        
        if ($mavenProductInfo == null && !empty($version))
        {
            throw new HttpNotFoundException($request);
        }

        if ($mavenProductInfo != null)
        {
            if (empty($version)) {
                $version = $mavenProductInfo->getLatestVersionToDisplay();
            } else if (!$mavenProductInfo->hasVersion($version)) {
                throw new HttpNotFoundException($request);
            }
            
            $mavenArtifacts = $mavenProductInfo->getMavenArtifactsForVersion($version);
            foreach ($mavenArtifacts as $artifact) {
                if ($artifact->getMakesSenseAsMavenDependency()) {
                    $mavenArtifactsAsDependency[] = $artifact;
                }
            }
            
            foreach ($mavenArtifacts as $artifact) {
                if ($artifact->isDocumentation() && $artifact->docExists($version)) {
                    $docArtifacts[] = $artifact;
                }
            }
        }
        
        $installButton = self::createInstallButton($request, $product, $version);
        
        return $this->view->render($response, 'market/product.twig', [
            'product' => $product,
            'mavenProductInfo' => $mavenProductInfo,
            'mavenArtifacts' => $mavenArtifacts,
            'mavenArtifactsAsDependency' => $mavenArtifactsAsDependency,
            'docArtifacts' => $docArtifacts,
            'selectedVersion' => $version,
            'installButton' => $installButton
        ]);
    }

    private static function createInstallButton(Request $request, Product $product, string $currentVersion): InstallButton
    {
        $uri = $request->getUri();
        $metaUrl = $uri->getScheme() . '://' . $uri->getHost() . $product->getMetaUrl($currentVersion);

        $cookies = $request->getCookieParams();
        $version = $cookies['ivy-version'] ?? '';

        $isDesigner = !empty($version);
        $reason = $product->getReasonWhyNotInstallable($version);
        return new InstallButton($isDesigner, $reason, $metaUrl);
    }
}

class InstallButton
{
    public bool $isDesigner;

    public string $reason;

    private string $metaUrl;

    function __construct(bool $isDesigner, string $reason, string $metaUrl)
    {
        $this->isDesigner = $isDesigner;
        $this->reason = $reason;
        $this->metaUrl = $metaUrl;
    }

    public function isEnabled(): bool
    {
        return empty($this->reason);
    }
    
    public function getJavascriptCallback(): string
    {
        return "install('". $this->metaUrl ."')";
    }
}
