<?php namespace Hampel\BannerGenerator\Template;

use Hampel\BannerGenerator\SubContainer\Banner;

class BannerFunction
{
	public static function render($templater, &$escape, $width, $height, $id = '', $class = '', $colour = '')
	{
		$width = intval($width);
		$height = intval($height);

		$app = \XF::app();

		/** @var Banner $banner */
		$banner = $app->get('banner');

		$banner->generateBanner($width, $height, $colour);

		$escape = false;

		if (!$banner->isValidSize($width, $height))
		{
			return '';
		}

		$divHtml = empty($id) ? '' : ' id="' . \XF::escapeString($id) . '"';

		$options = $app->options();
		$defaultClasses = $options['hampelBannerGeneratorDefaultClasses'];
		$classes = empty($defaultClasses) ? $class : trim("{$defaultClasses} {$class}");
		$classHtml = empty($classes) ? '' : ' class="' . \XF::escapeString($classes) . '"';

		$srcHtml = ' src="' . \XF::escapeString($banner->getBannerUrl($width, $height, $colour)) . '"';
		$altHtml = ' alt="' . $width . 'x' . $height . ' banner"';

		$styleHtml = ' style="width: ' . $width . 'px; height: ' . $height . 'px;"';

		return '<div' . $divHtml . $classHtml . $styleHtml . '>' . PHP_EOL . "\t" .
			'<img' . $srcHtml . $altHtml . ' />' . PHP_EOL .
			'</div>';
	}
}
