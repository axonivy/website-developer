<?php

namespace app\pages\market;

use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;
use app\domain\util\CookieUtil;
use app\domain\market\Market;
use app\domain\market\Product;
use Slim\Psr7\Request;
use app\domain\market\MavenProductInfo;
use app\domain\market\ProductDescription;
use app\domain\market\ProductMavenArtifactDownloader;
use app\domain\maven\MavenArtifact;
use app\domain\market\OpenAPIProvider;

class ProductAction
{

  private Twig $view;

  public function __construct(Twig $view)
  {
    $this->view = $view;
  }

  public function __invoke($request, $response, $args)
  {
    $key = $args['key'] ?? '';
    $product = Market::getProductByKey($key);
    if ($product == null) {
      throw new HttpNotFoundException($request);
    }

    $installNow = isset($request->getQueryParams()['installNow']);
    $mavenProductInfo = $product->getMavenProductInfo();
    $version = $args['version'] ?? '';
    $mavenArtifactsAsDependency = [];
    $mavenArtifacts = [];
    $docUrl = '';

    if ($mavenProductInfo == null && !empty($version)) {
      throw new HttpNotFoundException($request);
    }

    if ($mavenProductInfo != null) {
      if (empty($version)) {
        $version = self::findBestMatchingVersionFromCookie($request, $mavenProductInfo);
        if (empty($version)) {
          $version = $mavenProductInfo->getLatestVersionToDisplay();
        }
      } else if (!$mavenProductInfo->hasVersion($version)) {
        throw new HttpNotFoundException($request);
      }

      $mavenArtifacts = $mavenProductInfo->getMavenArtifactsForVersion($version);
      foreach ($mavenArtifacts as $artifact) {
        if ($artifact->getMakesSenseAsMavenDependency()) {
          $mavenArtifactsAsDependency[] = $artifact;
        }
      }
      
      $docArtifact = $mavenProductInfo->getFirstDocArtifact();
      if ($docArtifact != null) {
        $exists = (new ProductMavenArtifactDownloader())->downloadArtifact($product, $docArtifact, $version);
        if ($exists) {
          $docUrl = $docArtifact->getDocUrl($product, $version);
        }
      }

      $mavenArtifacts = array_filter($mavenArtifacts, fn(MavenArtifact $a) => !$a->isProductArtifact());
    }

    $installButton = self::createInstallButton($request, $product, $version);
    
    $getInTouchLink = 'https://www.axonivy.com/marketplace/contact/?market_solutions=' . $product->getKey();

    if (!empty($version)) {      
      (new ProductMavenArtifactDownloader())->download($product, $version);
    }
    $productDescription = ProductDescription::create($product, $version);
    
    $openApiProvider = new OpenAPIProvider($product);
    $openApiUrl = $openApiProvider->getOpenApiUrl($version);
    
    $productVersion = $version;
    if (empty($productVersion)) {
      $productVersion = $product->getVersion();
    }
    
    return $this->view->render($response, 'market/product.twig', [
      'product' => $product,
      'mavenProductInfo' => $mavenProductInfo,
      'productDescription' => $productDescription,
      'mavenArtifacts' => $mavenArtifacts,
      'mavenArtifactsAsDependency' => $mavenArtifactsAsDependency,
      'selectedVersion' => $version,
      'installButton' => $installButton,
      'getInTouchLink' => $getInTouchLink,
      'openApiUrl' => $openApiUrl,
      'version' => $productVersion,
      'docUrl' => $docUrl,
      'installNow' => $installNow
    ]);
  }

  private static function createInstallButton(Request $request, Product $product, string $currentVersion): InstallButton
  {
    $version = self::readIvyVersionCookie($request);
    $isDesigner = !empty($version);
    $reason = $product->getReasonWhyNotInstallable($isDesigner, $currentVersion);
    $isShow = $product->isInstallable($currentVersion);
    return new InstallButton($isDesigner, $reason, $product, $isShow, $request, $currentVersion);
  }

  private static function readIvyVersionCookie(Request $request)
  {
    $cookies = $request->getCookieParams();
    return $cookies['ivy-version'] ?? CookieUtil::setCookieOfQueryParam($request, 'ivy-version');
  }
  
  private static function findBestMatchingVersionFromCookie(Request $request, MavenProductInfo $mavenProductInfo)
  {
    $version = self::readIvyVersionCookie($request);
    if (empty($version)) {
      return '';
    }
    return $mavenProductInfo->findBestMatchingVersion($version);
  }
}

class InstallButton
{
  public bool $isDesigner;
  public string $reason;
  public bool $isShow;
  private Product $product;
  private Request $request;
  private string $currentVersion;
  
  function __construct(bool $isDesigner, string $reason, Product $product, bool $isShow, Request $request, string $currentVersion)
  {
    $this->isDesigner = $isDesigner;
    $this->reason = $reason;
    $this->product = $product;
    $this->isShow = $isShow;
    $this->request = $request;
    $this->currentVersion = $currentVersion;
  }

  public function isEnabled(): bool
  {
    return empty($this->reason);
  }

  public function getJavascriptCallback(): string
  {
    return "install('" . $this->getProductJsonUrl($this->currentVersion) . "')";
  }
  
  public function getMultipleVersions(): bool
  {
    return $this->product->getMavenProductInfo() != null;
  }

  public function getProductJsonUrl($version): string
  {
    $uri = $this->request->getUri();
    $baseUrl = $uri->getScheme() . '://' . $uri->getHost();
    return $baseUrl . $this->product->getProductJsonUrl($version);
  }
}
