<?php

namespace app\domain;

use app\domain\util\ArrayUtil;

class ReleaseType
{
  private static $typeCache;

  public static function LTS(): ReleaseType
  {
    $type = new ReleaseType();
    $type->key = 'lts';
    $type->archiveKey = $type->key;
    $type->name = 'Long Term Support';
    $type->shortName = 'LTS';
    $type->releaseInfoSupplier = fn (string $key) => ReleaseInfoRepository::getLatestLongTermSupport();
    $type->allReleaseInfoSupplier = fn (string $key) => ReleaseInfoRepository::getLongTermSupportVersions();
    $type->isDevRelease = false;
    $type->headline = '<p>Get the latest stable <a href="/release-cycle" style="text-decoration:underline;font-weight:bold;">Long Term Support</a> version of the Axon Ivy Platform. Or download the <a href="/download/leading-edge">Leading Edge</a> version.';
    $type->banner = '';
    $type->archiveLinkSupplier = fn (ReleaseInfo $releaseInfo) => '/download/archive/' . $releaseInfo->minorVersion();
    $type->promotedDevVersion = false;
    return $type;
  }

  public static function LE(): ReleaseType
  {
    $type = new ReleaseType();
    $type->key = 'leading-edge';
    $type->archiveKey = $type->key;
    $type->name = 'Leading Edge';
    $type->shortName = 'LE';
    $type->releaseInfoSupplier = fn (string $key) => ReleaseInfoRepository::getLeadingEdge();
    $type->isDevRelease = false;
    $type->headline = '<p>Become an early adopter and take the <a href="/release-cycle" style="text-decoration:underline;font-weight:bold;">Leading Edge</a> road with newest features but frequent migrations.</p>';
    $type->banner = '<i class="si si-bell"></i> <b>Get familiar with our <a href="/release-cycle">release cycle</a> before you are going to use Leading Edge.</b>';
    $type->archiveLinkSupplier = fn (ReleaseInfo $releaseInfo) => '/download/archive/' . $releaseInfo->majorVersion();
    $type->promotedDevVersion = false;
    return $type;
  }

  public static function SPRINT(): ReleaseType
  {
    $type = self::createDevReleaseType();
    $type->key = 'sprint';
    $type->name = 'Sprint Release';
    $type->shortName = 'Sprint';
    $type->promotedDevVersion = true;
    return $type;
  }

  public static function NIGHTLY(): ReleaseType
  {
    $type = self::createDevReleaseType();
    $type->key = 'nightly';
    $type->name = 'Nightly Build';
    $type->shortName = 'Nightly';
    $type->promotedDevVersion = true;
    return $type;
  }

  public static function DEV(): ReleaseType
  {
    $type = self::createDevReleaseType();
    $type->key = 'dev';
    $type->name = 'Development Build';
    $type->shortName = 'dev';
    $type->promotedDevVersion = false;
    return $type;
  }

  public static function LTS_NIGHTLY($majorVersion): ReleaseType
  {
    $type = self::createDevReleaseType();
    $type->key = 'nightly-' . $majorVersion;
    $type->name = 'Nightly Build ' . $majorVersion;
    $type->shortName = 'Nightly ' . $majorVersion;
    $type->promotedDevVersion = true;
    return $type;
  }

  private static function createDevReleaseType(): ReleaseType
  {
    $type = new ReleaseType();
    $type->archiveKey = 'unstable';
    $type->releaseInfoSupplier = fn (string $key) => self::devReleaseInfoSupplier($key);
    $type->isDevRelease = true;
    $type->headline = '<p>Our development releases are very unstable and only available for testing purposes.</p>';
    $type->banner = '<i class="si si-bell" style="background-color:#e62a10;"></i> <b style="color:#e62a10;">These artifacts are for testing purposes only. Never use them on a productive system!</b>';
    $type->archiveLinkSupplier = fn (ReleaseInfo $releaseInfo) => '/download/archive/unstable';
    return $type;
  }

  private static function devReleaseInfoSupplier(string $key): ?ReleaseInfo
  {
    return ReleaseInfoRepository::getBestMatchingVersion($key);
  }
  
  public static function VERSION(string $version): ?ReleaseType
  {
    if (!Version::isValidVersionNumber($version)) {
      return null;
    }
    
    if (ReleaseInfoRepository::isOrWasLtsVersion(new Version($version))) {
      $type = ReleaseType::LTS();
    } else {
      $type = ReleaseType::LE();
    }
    $type->releaseInfoSupplier = fn (string $key) => ReleaseInfoRepository::getBestMatchingVersion($version);
    return $type;
  }

  public static function isLTS(ReleaseType $releaseType): bool
  {
    return $releaseType->key == self::LTS()->key;
  }

  private static function types(): array
  {
    if (!empty(self::$typeCache)) {
      return self::$typeCache;
    }

    $nightlyLtsReleases = [];
    foreach (ReleaseInfoRepository::getLongTermSupportVersions() as $releaseInfo) {
      $nightlyLtsRelease = self::LTS_NIGHTLY($releaseInfo->getVersion()->getMajorVersion());
      if ($nightlyLtsRelease->releaseInfo() != null) {
        $nightlyLtsReleases[] = $nightlyLtsRelease;
      }
    }

    self::$typeCache = array_merge(
      [
        self::LTS(),
        self::LE(),
        self::SPRINT(),
        self::NIGHTLY(),
        self::DEV(),
      ],
      $nightlyLtsReleases
    );

    return self::$typeCache;
  }

  public static function PROMOTED_DEV_TYPES(): array
  {
    return array_filter(self::types(), fn (ReleaseType $releaseType) => $releaseType->promotedDevVersion);
  }

  public static function byKey(string $key): ?ReleaseType
  {
    $types = array_filter(self::types(), fn (ReleaseType $type) => $type->key == $key);
    return ArrayUtil::getLastElementOrNull($types);
  }

  public static function byArchiveKey(string $archiveKey): array
  {
    return array_filter(self::types(), fn (ReleaseType $type) => $type->archiveKey == $archiveKey);
  }

  private string $key;

  private string $archiveKey;

  private string $name;

  private string $shortName;

  private $releaseInfoSupplier;

  private $allReleaseInfoSupplier;

  private bool $isDevRelease;

  private string $headline;

  private string $banner;

  private $archiveLinkSupplier;

  private bool $promotedDevVersion;

  public function releaseInfo(): ?ReleaseInfo
  {
    return $this->releaseInfoSupplier->call($this, $this->key);
  }

  public function allReleaseInfos(): array
  {
    if ($this->allReleaseInfoSupplier != null) {
      return $this->allReleaseInfoSupplier->call($this, $this->key);
    }
    return array_filter([
      $this->releaseInfo()
    ]);
  }

  public function key(): string
  {
    return $this->key;
  }

  public function archiveKey(): string
  {
    return $this->archiveKey;
  }

  public function name(): string
  {
    return $this->name;
  }

  public function shortName(): string
  {
    return $this->shortName;
  }

  public function isDevRelease(): bool
  {
    return $this->isDevRelease;
  }

  public function banner(): string
  {
    return $this->banner;
  }

  public function headline(): string
  {
    return $this->headline;
  }

  public function downloadLink(): string
  {
    return '/download/' . $this->key;
  }

  public function archiveLink(ReleaseInfo $releaseInfo): string
  {
    return $this->archiveLinkSupplier->call($this, $releaseInfo);
  }
}
