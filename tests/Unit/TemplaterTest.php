<?php namespace Tests\Unit;

use Hampel\BannerGenerator\SubContainer\Banner;
use Tests\TestCase;
use XF\Template\Templater;

class TemplaterTest extends TestCase
{
	/** @var Templater */
	protected $templater;

	protected function setUp() : void
	{
		parent::setUp();

		$class = $this->app()->extendClass(Templater::class);
		$this->templater = new $class($this->app(), \XF::language(), '');

		$this->mock('banner', Banner::class, function ($mock) {
			$mock->allows([
				'generateBanner' => null,
				'getBannerUrl' => 'foo'
			]);
		});
	}

	// ------------------------------------------------

	public function test_instantiation()
	{
		$templater = $this->templater;

		$this->assertTrue($templater instanceof \Hampel\BannerGenerator\XF\Template\Templater);
	}

	public function test_Banner_defaults()
	{
		$this->setOption('hampelBannerGeneratorDefaultClasses', '');

		$escape = true;
		$banner = $this->templater->fnBanner($this->templater, $escape, 200, 100);

		$expected = '<div style="width: 200px; height: 100px;">' . PHP_EOL . "\t" .
						'<img src="foo" alt="200x100 banner" />' . PHP_EOL .
					'</div>';

		$this->assertEquals($expected, $banner);
	}

	public function test_Banner_defaults_default_class()
	{
		$this->setOption('hampelBannerGeneratorDefaultClasses', 'default-class');

		$escape = true;
		$banner = $this->templater->fnBanner($this->templater, $escape, 200, 100);

		$expected = '<div class="default-class" style="width: 200px; height: 100px;">' . PHP_EOL . "\t" .
						'<img src="foo" alt="200x100 banner" />' . PHP_EOL .
					'</div>';

		$this->assertEquals($expected, $banner);
	}

	public function test_Banner_id()
	{
		$this->setOption('hampelBannerGeneratorDefaultClasses', '');

		$escape = true;
		$banner = $this->templater->fnBanner($this->templater, $escape, 200, 100, 'div-id');

		$expected = '<div id="div-id" style="width: 200px; height: 100px;">' . PHP_EOL . "\t" .
						'<img src="foo" alt="200x100 banner" />' . PHP_EOL .
					'</div>';

		$this->assertEquals($expected, $banner);
	}

	public function test_Banner_id_class()
	{
		$this->setOption('hampelBannerGeneratorDefaultClasses', '');

		$escape = true;
		$banner = $this->templater->fnBanner($this->templater, $escape, 200, 100, 'div-id', 'class-id');

		$expected = '<div id="div-id" class="class-id" style="width: 200px; height: 100px;">' . PHP_EOL . "\t" .
						'<img src="foo" alt="200x100 banner" />' . PHP_EOL .
					'</div>';

		$this->assertEquals($expected, $banner);
	}

	public function test_Banner_id_class_default_class()
	{
		$this->setOption('hampelBannerGeneratorDefaultClasses', 'default-class');

		$escape = true;
		$banner = $this->templater->fnBanner($this->templater, $escape, 200, 100, 'div-id', 'class-id');

		$expected = '<div id="div-id" class="default-class class-id" style="width: 200px; height: 100px;">' . PHP_EOL . "\t" .
						'<img src="foo" alt="200x100 banner" />' . PHP_EOL .
					'</div>';

		$this->assertEquals($expected, $banner);
	}
}
