<?php namespace Hampel\BannerGenerator;

use Hampel\BannerGenerator\SubContainer\Banner;
use Hampel\BannerGenerator\Template\BannerFunction;
use XF\App;
use XF\Container;

class Listener
{
	/**
	 * Setup banner container
	 *
	 * @param App $app
	 *
	 * @return void
	 */
	public static function appSetup(App $app)
	{
		$container = $app->container();

		$container['banner'] = function(Container $c) use ($app)
		{
			$class = $app->extendClass(Banner::class);
			return new $class($c, $app);
		};
	}

	/**
	 * Add our Banner template function
	 *
	 * @param Container $container
	 * @param \XF\Template\Templater $templater
	 *
	 * @return void
	 */
	public static function templaterSetup(\XF\Container $container, \XF\Template\Templater &$templater)
	{
		$templater->addFunction('banner', [BannerFunction::class, 'render']);
	}

	/**
	 * Declare which of this add-on's settings are secrets. It has none.
	 *
	 * @param array<string, array<string, string[]>> $declarations
	 *
	 * @return void
	 */
	public static function adminApiRedaction(array &$declarations)
	{
		$declarations['Hampel/BannerGenerator'] = [];
	}
}
