<?php namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
	/**
	 * Keeps the Feature suite collecting a test, and asserts something that can fail: the app booted.
	 */
	public function test_the_application_boots(): void
	{
		$this->assertInstanceOf(\XF\App::class, $this->app());
	}
}
