<?php namespace Tests\Unit;

use Hampel\BannerGenerator\Cli\Command\CreateBanner;
use Hampel\BannerGenerator\SubContainer\Banner;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class CreateBannerTest extends TestCase
{
	/** @var BufferedOutput */
	protected $output;

	protected function setUp() : void
	{
		parent::setUp();

		$this->output = new BufferedOutput();
	}

	// ArgvInput is what the console parses with, and it treats flags differently from ArrayInput
	protected function runCommand(array $args)
	{
		$command = new CreateBanner();

		return $command->run(new ArgvInput(array_merge(['cmd.php'], $args)), $this->output);
	}

	protected function mockBanner($generateArgs = null, $returns = null)
	{
		$this->mock('banner', Banner::class, function ($mock) use ($generateArgs, $returns) {
			$mock->allows('getColourKeys')->andReturns(['red', 'blue']);

			if ($generateArgs === null)
			{
				$mock->expects('generateBanner')->never();
			}
			else
			{
				$mock->expects('generateBanner')->with(...$generateArgs)->andReturns($returns);
			}
		});
	}

	// ------------------------------------------------

	public function test_missing_width_fails()
	{
		$this->mockBanner();

		$this->assertEquals(1, $this->runCommand(['--height=50']));
		$this->assertStringContainsString('Width must be specified', $this->output->fetch());
	}

	public function test_zero_height_fails()
	{
		$this->mockBanner();

		$this->assertEquals(1, $this->runCommand(['--width=100', '--height=0']));
		$this->assertStringContainsString('Height must be specified', $this->output->fetch());
	}

	public function test_invalid_colour_fails()
	{
		$this->mockBanner();

		$this->assertEquals(1, $this->runCommand(['--width=100', '--height=50', '--colour=foo']));
		$this->assertStringContainsString('Colour must be one of [red, blue]', $this->output->fetch());
	}

	public function test_without_force_does_not_force()
	{
		$this->mockBanner([100, 50, 'red', false], 'data://foo/100x50-red.png');

		$this->assertEquals(0, $this->runCommand(['--width=100', '--height=50', '--colour=red']));
		$this->assertStringContainsString('Banner created: [data://foo/100x50-red.png]', $this->output->fetch());
	}

	public function test_force_forces()
	{
		$this->mockBanner([100, 50, 'red', true], 'data://foo/100x50-red.png');

		$this->assertEquals(0, $this->runCommand(['--width=100', '--height=50', '--colour=red', '--force']));
	}

	public function test_short_force_forces()
	{
		$this->mockBanner([100, 50, 'red', true], 'data://foo/100x50-red.png');

		$this->assertEquals(0, $this->runCommand(['-x', '100', '-y', '50', '-c', 'red', '-f']));
	}

	public function test_force_does_not_accept_a_value()
	{
		$this->mockBanner();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The "--force" option does not accept a value.');

		$this->runCommand(['--width=100', '--height=50', '--force=1']);
	}

	public function test_existing_banner_is_reported()
	{
		$this->mockBanner([100, 50, 'red', false], '');

		$this->assertEquals(0, $this->runCommand(['--width=100', '--height=50', '--colour=red']));
		$this->assertStringContainsString('Banner already exists', $this->output->fetch());
	}

	public function test_generation_failure_fails()
	{
		$this->mockBanner([100, 50, 'red', false], null);

		$this->assertEquals(1, $this->runCommand(['--width=100', '--height=50', '--colour=red']));
		$this->assertStringContainsString('Banner could not be generated', $this->output->fetch());
	}
}
