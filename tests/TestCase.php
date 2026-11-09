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

    protected function setUp(): void
    {
        parent::setUp();

        // XenForo boots with E_DEPRECATED masked, which hides every PHP deprecation from
        // failOnDeprecation. The Flysystem 1.x memory adapter (dev-only, pinned) carries one of
        // its own, so load it while the mask still applies.
        class_exists(\League\Flysystem\Memory\MemoryAdapter::class);
        error_reporting(E_ALL);
    }
}
