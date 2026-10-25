<?php namespace Tests\Unit;

use Hampel\BannerGenerator\SubContainer\Banner;
use Tests\TestCase;
use XF\Phrase;

class SubcontainerTest extends TestCase
{
	protected Banner $banner;

	protected function setUp() : void
	{
		parent::setUp();

		$banner = $this->app()->get('banner');
		$this->assertInstanceOf(Banner::class, $banner);
		$this->banner = $banner;
	}

	protected function assertBannerImage(string $path, int $width, int $height, string $colourKey): void
	{
		$data = $this->app()->fs()->read($path);
		$this->assertIsString($data, "{$path} could not be read");

		$info = getimagesizefromstring($data);
		$this->assertNotFalse($info, "{$path} is not an image");
		$this->assertEquals([$width, $height, IMAGETYPE_PNG], [$info[0], $info[1], $info[2]]);

		// the label is drawn from (5, 5), so the corner is always background
		$im = imagecreatefromstring($data);
		$this->assertNotFalse($im);
		$index = imagecolorat($im, 0, 0);
		$this->assertNotFalse($index);
		$pixel = imagecolorsforindex($im, $index);

		$colour = $this->banner->getColour($colourKey);
		$this->assertNotNull($colour);
		$this->assertEquals(
			$colour['background'],
			[$pixel['red'], $pixel['green'], $pixel['blue']]
		);
	}

	// ------------------------------------------------

	public function test_initialisation(): void
	{
		$this->assertArrayHasKey('black', $this->banner->getColours());
	}

	public function test_getColourKeys_returns_array(): void
	{
		$colourKeys = $this->banner->getColourKeys();

		$this->assertContains('black', $colourKeys);
	}

	public function test_getColour_returns_null(): void
	{
		$this->assertNull($this->banner->getColour(null));
		$this->assertNull($this->banner->getColour(''));
		$this->assertNull($this->banner->getColour(1));
		$this->assertNull($this->banner->getColour('foo'));
	}

	public function test_getColour_returns_colour_array(): void
	{
		$colour = $this->banner->getColour('black');
		$this->assertIsArray($colour);
		$this->assertArrayHasKey('background', $colour);
		$this->assertArrayHasKey('text', $colour);
		$this->assertArrayHasKey('phrase', $colour);
	}

	public function test_getColoursForSelect_returns_array(): void
	{
		$colours = $this->banner->getColoursForSelect();
		$this->assertArrayHasKey('black', $colours);
		$this->assertInstanceOf(Phrase::class, $colours['black']);
	}

	public function test_getAbstractedDataPath(): void
	{
		$this->setOption('hampelBannerGeneratorSavePath', 'foo');

		$path = $this->banner->getAbstractedDataPath(100, 50, 'bar');

		$this->assertEquals('data://foo/100x50-bar.png', $path);
	}

	public function test_getAbstractedDataPath_default_colour(): void
	{
		$this->setOption('hampelBannerGeneratorSavePath', 'foo');
		$this->setOption('hampelBannerGeneratorDefaultColour', 'baz');

		$path = $this->banner->getAbstractedDataPath(100, 50);

		$this->assertEquals('data://foo/100x50-baz.png', $path);
	}

	public function test_getBannerUrl(): void
	{
		$this->setOption('hampelBannerGeneratorSavePath', 'foo');

		$path = $this->banner->getBannerUrl(100, 50, 'bar');

		$basePath = $this->app()->request()->getBasePath();
		$externalDataUrl = $this->app()->config('externalDataUrl');
		$this->assertIsString($externalDataUrl);
		$basePath = rtrim($basePath, '/') . '/';

		$this->assertEquals($basePath . $externalDataUrl . '/foo/100x50-bar.png', $path);
	}

	public function test_getBannerUrl_default_colour(): void
	{
		$this->setOption('hampelBannerGeneratorSavePath', 'foo');
		$this->setOption('hampelBannerGeneratorDefaultColour', 'baz');

		$path = $this->banner->getBannerUrl(100, 50);

		$basePath = $this->app()->request()->getBasePath();
		$externalDataUrl = $this->app()->config('externalDataUrl');
		$this->assertIsString($externalDataUrl);
		$basePath = rtrim($basePath, '/') . '/';

		$this->assertEquals($basePath . $externalDataUrl . '/foo/100x50-baz.png', $path);
	}

	public function test_generateBanner_logs_error_for_invalid_colour(): void
	{
		$this->fakesErrors();

		$this->banner->generateBanner(100, 50, 'foo');

		$this->assertErrorLogged("Invalid colour specified for banner test: foo");
	}

	public function test_isValidSize(): void
	{
		$this->assertTrue($this->banner->isValidSize(100, 50));
		$this->assertTrue($this->banner->isValidSize('100', '50'));
		$this->assertFalse($this->banner->isValidSize(0, 50));
		$this->assertFalse($this->banner->isValidSize(100, -1));
		$this->assertFalse($this->banner->isValidSize('auto', 50));
	}

	public function test_generateBanner_logs_error_for_invalid_size(): void
	{
		$this->fakesErrors();

		$this->assertNull($this->banner->generateBanner(0, 50, 'red'));

		$this->assertErrorLogged("Invalid dimensions specified for banner test: 0x50");
	}

	public function test_generateBanner_returns_empty_for_existing_image(): void
	{
		$this->swapFs('data');
		$this->setOption('hampelBannerGeneratorSavePath', 'foo');

		$path = 'data://foo/100x50-red.png';

		// check there's nothing there now
		$this->assertFalse($this->app()->fs()->has($path));

		// create a file at our destination
		$this->app()->fs()->write($path, '');

		$this->assertTrue($this->app()->fs()->has($path));
		$this->assertEquals(0, $this->app()->fs()->getSize($path));

		$this->assertEmpty($this->banner->generateBanner(100, 50, 'red'));

		// check new file hasn't been created
		$this->assertTrue($this->app()->fs()->has($path));
		$this->assertEquals(0, $this->app()->fs()->getSize($path));
	}

	public function test_generateBanner_generates_image(): void
	{
		$this->swapFs('data');
		$this->setOption('hampelBannerGeneratorSavePath', 'foo');

		$path = 'data://foo/100x50-red.png';

		// check there's nothing there now
		$this->assertFalse($this->app()->fs()->has($path));

		$this->assertEquals($path, $this->banner->generateBanner(100, 50, 'red'));

		// check new file has been created
		$this->assertTrue($this->app()->fs()->has($path));
		$this->assertBannerImage($path, 100, 50, 'red');
	}

	public function test_generateBanner_generates_default_image(): void
	{
		$this->swapFs('data');
		$this->setOption('hampelBannerGeneratorSavePath', 'foo');
		$this->setOption('hampelBannerGeneratorDefaultColour', 'blue');

		$path = 'data://foo/100x50-blue.png';

		// check there's nothing there now
		$this->assertFalse($this->app()->fs()->has($path));

		$this->assertEquals($path, $this->banner->generateBanner(100, 50));

		// check new file has been created
		$this->assertTrue($this->app()->fs()->has($path));
		$this->assertBannerImage($path, 100, 50, 'blue');
	}

	public function test_generateBanner_generates_new_image_when_forced(): void
	{
		$this->swapFs('data');
		$this->setOption('hampelBannerGeneratorSavePath', 'foo');

		$path = 'data://foo/100x50-red.png';

		// check there's nothing there now
		$this->assertFalse($this->app()->fs()->has($path));

		// create a file at our destination
		$this->app()->fs()->write($path, '');

		// check there's nothing there now
		$this->assertTrue($this->app()->fs()->has($path));
		$this->assertEquals(0, $this->app()->fs()->getSize($path));

		$this->assertEquals($path, $this->banner->generateBanner(100, 50, 'red', true));

		// check new file has been created
		$this->assertTrue($this->app()->fs()->has($path));
		$this->assertBannerImage($path, 100, 50, 'red');
	}
}
