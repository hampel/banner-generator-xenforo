<?php namespace Tests;

use Hampel\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /*
     * Set $rootDir to '../../../..' if you use a vendor in your addon id (ie <Vendor/AddonId>)
     * Otherwise, set this to '../../..' for no vendor
     *
     * No trailing slash!
     */
    protected $rootDir = '../../../..';

    protected $addonsToLoad = ['Hampel/BannerGenerator'];

    private int $errorReporting;

    protected function setUp(): void
    {
        parent::setUp();

        // XenForo boots with E_DEPRECATED masked, which hides every PHP deprecation from
        // failOnDeprecation. Flysystem 1.x carries PHP 8.4 deprecations of its own: the memory
        // adapter (dev-only, pinned) would throw, and core's copy under src/vendor/ is logged to
        // the forum's error log instead. So load them while the mask still applies.
        class_exists(\League\Flysystem\Memory\MemoryAdapter::class);
        class_exists(\League\Flysystem\MountManager::class);
        class_exists(\League\Flysystem\Filesystem::class);
        class_exists(\League\Flysystem\EventableFilesystem\EventableFilesystem::class);

        $this->errorReporting = error_reporting(E_ALL);
    }

    protected function tearDown(): void
    {
        error_reporting($this->errorReporting);

        parent::tearDown();
    }
}
