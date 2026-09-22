<?php namespace Hampel\BannerGenerator\XF\Template;

use Hampel\BannerGenerator\SubContainer\Banner;

class Templater extends XFCP_Templater
{
	public function fnBanner($templater, &$escape, $width, $height, $id = '', $class = '', $colour = '')
	{
		$width = intval($width);
		$height = intval($height);

		/** @var Banner $banner */
		$banner = $this->app->get('banner');

		$banner->generateBanner($width, $height, $colour);

		$escape = false;

		$divHtml = empty($id) ? '' : ' id="' . \XF::escapeString($id) . '"';

		$options = $this->app->options();
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
