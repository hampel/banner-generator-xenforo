<?php namespace Hampel\BannerGenerator;

use Hampel\BannerGenerator\SubContainer\Banner;
use XF\App;
use XF\Container;

class Listener
{
	/**
	 * Setup banner container
	 *
	 * @param App $app
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
	 */
	public static function templaterSetup(\XF\Container $container, \XF\Template\Templater &$templater)
	{
		$templater->addFunction('banner', 'fnBanner');
	}

}