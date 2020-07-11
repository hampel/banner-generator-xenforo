<?php namespace Tests\Unit;

use Hampel\BannerTest\SubContainer\Banner;
use Tests\TestCase;
use XF\Phrase;

class SubcontainerTest extends TestCase
{
	/** @var Banner */
	protected $banner;

	protected function setUp() : void
	{
		parent::setUp();

		$this->banner = $this->app()->get('banner');
	}

	// ------------------------------------------------

	public function test_initialisation()
	{
		$this->assertIsArray($this->banner->getColours());
	}

	public function test_getColourKeys_returns_array()
	{
		$colourKeys = $this->banner->getColourKeys();

		$this->assertIsArray($colourKeys);
		$this->assertTrue(in_array('black', $colourKeys));
	}

	public function test_getColour_returns_null()
	{
		$this->assertNull($this->banner->getColour(null));
		$this->assertNull($this->banner->getColour(''));
		$this->assertNull($this->banner->getColour(1));
		$this->assertNull($this->banner->getColour('foo'));
	}

	public function test_getColour_returns_colour_array()
	{
		$colour = $this->banner->getColour('black');
		$this->assertIsArray($colour);
		$this->assertArrayHasKey('background', $colour);
		$this->assertArrayHasKey('text', $colour);
		$this->assertArrayHasKey('phrase', $colour);
	}

	public function test_getColoursForSelect_returns_array()
	{
		$colours = $this->banner->getColoursForSelect();
		$this->assertIsArray($colours);
		$this->assertArrayHasKey('black', $colours);
		$this->assertInstanceOf(Phrase::class, $colours['black']);
	}

	public function test_getAbstractedDataPath()
	{
		$this->setOption('hampelBannerGeneratorSavePath', 'foo');

		$path = $this->banner->getAbstractedDataPath(100, 50, 'bar');

		$this->assertEquals('data://foo/100x50-bar.png', $path);
	}

	public function test_getAbstractedDataPath_default_colour()
	{
		$this->setOption('hampelBannerGeneratorSavePath', 'foo');
		$this->setOption('hampelBannerGeneratorDefaultColour', 'baz');

		$path = $this->banner->getAbstractedDataPath(100, 50);

		$this->assertEquals('data://foo/100x50-baz.png', $path);
	}

	public function test_getBannerUrl()
	{
		$this->setOption('hampelBannerGeneratorSavePath', 'foo');

		$path = $this->banner->getBannerUrl(100, 50, 'bar');

		$basePath = rtrim($this->app()->request()->getBasePath(), '/') . '/';
		$externalDataUrl = $this->app()->config('externalDataUrl');

		$this->assertEquals($basePath . $externalDataUrl . '/foo/100x50-bar.png', $path);
	}

	public function test_getBannerUrl_default_colour()
	{
		$this->setOption('hampelBannerGeneratorSavePath', 'foo');
		$this->setOption('hampelBannerGeneratorDefaultColour', 'baz');

		$path = $this->banner->getBannerUrl(100, 50);

		$basePath = rtrim($this->app()->request()->getBasePath(), '/') . '/';
		$externalDataUrl = $this->app()->config('externalDataUrl');

		$this->assertEquals($basePath . $externalDataUrl . '/foo/100x50-baz.png', $path);
	}

	public function test_generateBanner_logs_error_for_invalid_colour()
	{
		$this->fakesErrors();

		$this->banner->generateBanner(100, 50, 'foo');

		$this->assertErrorLogged("Invalid colour specified for banner test: foo");
	}

	public function test_generateBanner_returns_empty_for_existing_image()
	{
		$this->swapFs('data');
		$this->setOption('hampelBannerGeneratorSavePath', 'foo');

		$path = 'data://foo/100x50-red.png';

		// check there's nothing there now
		$this->assertFalse($this->app->fs()->has($path));

		// create a file at our destination
		$this->app->fs()->write($path, '');

		$this->assertTrue($this->app->fs()->has($path));
		$this->assertEquals(0, $this->app->fs()->getSize($path));

		$this->assertEmpty($this->banner->generateBanner(100, 50, 'red'));

		// check new file hasn't been created
		$this->assertTrue($this->app->fs()->has($path));
		$this->assertEquals(0, $this->app->fs()->getSize($path));
	}

	public function test_generateBanner_generates_image()
	{
		$this->swapFs('data');
		$this->setOption('hampelBannerGeneratorSavePath', 'foo');

		$path = 'data://foo/100x50-red.png';

		// check there's nothing there now
		$this->assertFalse($this->app->fs()->has($path));

		$this->assertEquals($path, $this->banner->generateBanner(100, 50, 'red'));

		// check new file has been created
		$this->assertTrue($this->app->fs()->has($path));
		$this->assertEquals(180, $this->app->fs()->getSize($path));
	}

	public function test_generateBanner_generates_default_image()
	{
		$this->swapFs('data');
		$this->setOption('hampelBannerGeneratorSavePath', 'foo');
		$this->setOption('hampelBannerGeneratorDefaultColour', 'blue');

		$path = 'data://foo/100x50-blue.png';

		// check there's nothing there now
		$this->assertFalse($this->app->fs()->has($path));

		$this->assertEquals($path, $this->banner->generateBanner(100, 50));

		// check new file has been created
		$this->assertTrue($this->app->fs()->has($path));
		$this->assertEquals(180, $this->app->fs()->getSize($path));
	}

	public function test_generateBanner_generates_new_image_when_forced()
	{
		$this->swapFs('data');
		$this->setOption('hampelBannerGeneratorSavePath', 'foo');

		$path = 'data://foo/100x50-red.png';

		// check there's nothing there now
		$this->assertFalse($this->app->fs()->has($path));

		// create a file at our destination
		$this->app->fs()->write($path, '');

		// check there's nothing there now
		$this->assertTrue($this->app->fs()->has($path));
		$this->assertEquals(0, $this->app->fs()->getSize($path));

		$this->assertEquals($path, $this->banner->generateBanner(100, 50, 'red', true));

		// check new file has been created
		$this->assertTrue($this->app->fs()->has($path));
		$this->assertEquals(180, $this->app->fs()->getSize($path));
	}
}
