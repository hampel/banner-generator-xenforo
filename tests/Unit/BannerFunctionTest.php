<?php namespace Tests\Unit;

use Hampel\BannerGenerator\SubContainer\Banner;
use Mockery\MockInterface;
use Tests\TestCase;
use XF\Template\Templater;

class BannerFunctionTest extends TestCase
{
	protected Templater $templater;

	protected function setUp() : void
	{
		parent::setUp();

		// the app's own templater, so the function is registered by the templater_setup listener
		$this->templater = $this->app()->templater();

		$this->mock('banner', Banner::class, function (MockInterface $mock) {
			$mock->allows([
				'generateBanner' => null,
				'getBannerUrl' => 'foo',
			]);
			$mock->allows('isValidSize')->andReturnUsing(function (int|string $width, int|string $height): bool {
				return intval($width) > 0 && intval($height) > 0;
			});
		});
	}

	// ------------------------------------------------

	protected function banner(mixed ...$args): string
	{
		$html = $this->templater->func('banner', $args);
		$this->assertIsString($html);

		return $html;
	}

	public function test_banner_function_is_registered(): void
	{
		$this->assertStringStartsWith('<div', $this->banner(200, 100));
	}

	public function test_Banner_defaults(): void
	{
		$this->setOption('hampelBannerGeneratorDefaultClasses', '');

		$banner = $this->banner(200, 100);

		$expected = '<div style="width: 200px; height: 100px;">' . PHP_EOL . "\t" .
						'<img src="foo" alt="200x100 banner" />' . PHP_EOL .
					'</div>';

		$this->assertEquals($expected, $banner);
	}

	public function test_Banner_defaults_default_class(): void
	{
		$this->setOption('hampelBannerGeneratorDefaultClasses', 'default-class');

		$banner = $this->banner(200, 100);

		$expected = '<div class="default-class" style="width: 200px; height: 100px;">' . PHP_EOL . "\t" .
						'<img src="foo" alt="200x100 banner" />' . PHP_EOL .
					'</div>';

		$this->assertEquals($expected, $banner);
	}

	public function test_Banner_id(): void
	{
		$this->setOption('hampelBannerGeneratorDefaultClasses', '');

		$banner = $this->banner(200, 100, 'div-id');

		$expected = '<div id="div-id" style="width: 200px; height: 100px;">' . PHP_EOL . "\t" .
						'<img src="foo" alt="200x100 banner" />' . PHP_EOL .
					'</div>';

		$this->assertEquals($expected, $banner);
	}

	public function test_Banner_id_class(): void
	{
		$this->setOption('hampelBannerGeneratorDefaultClasses', '');

		$banner = $this->banner(200, 100, 'div-id', 'class-id');

		$expected = '<div id="div-id" class="class-id" style="width: 200px; height: 100px;">' . PHP_EOL . "\t" .
						'<img src="foo" alt="200x100 banner" />' . PHP_EOL .
					'</div>';

		$this->assertEquals($expected, $banner);
	}

	public function test_Banner_escapes_attributes(): void
	{
		$this->setOption('hampelBannerGeneratorDefaultClasses', '');

		$banner = $this->banner('200"', 100, 'a"b', 'c<d');

		$expected = '<div id="a&quot;b" class="c&lt;d" style="width: 200px; height: 100px;">' . PHP_EOL . "\t" .
						'<img src="foo" alt="200x100 banner" />' . PHP_EOL .
					'</div>';

		$this->assertEquals($expected, $banner);
	}

	public function test_Banner_returns_nothing_for_invalid_size(): void
	{
		$this->assertSame('', $this->banner(0, 90));
		$this->assertSame('', $this->banner('auto', 90));
		$this->assertSame('', $this->banner(728, -1));
	}

	public function test_Banner_id_class_default_class(): void
	{
		$this->setOption('hampelBannerGeneratorDefaultClasses', 'default-class');

		$banner = $this->banner(200, 100, 'div-id', 'class-id');

		$expected = '<div id="div-id" class="default-class class-id" style="width: 200px; height: 100px;">' . PHP_EOL . "\t" .
						'<img src="foo" alt="200x100 banner" />' . PHP_EOL .
					'</div>';

		$this->assertEquals($expected, $banner);
	}
}
