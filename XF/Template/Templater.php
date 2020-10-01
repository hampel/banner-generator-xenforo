<?php namespace Hampel\BannerGenerator\XF\Template;

use Hampel\BannerGenerator\SubContainer\Banner;

class Templater extends XFCP_Templater
{
	public function fnBanner($templater, &$escape, $width, $height, $id = '', $class = '', $colour = '')
	{
		/** @var Banner $banner */
		$banner = $this->app->get('banner');

		$banner->generateBanner($width, $height, $colour);

		$escape = false;

		$divHtml = empty($id) ? '' : ' id="' . $id . '"';

		$options = $this->app->options();
		$defaultClasses = $options['hampelBannerGeneratorDefaultClasses'];
		$classes = empty($defaultClasses) ? $class : trim("{$defaultClasses} {$class}");
		$classHtml = empty($classes) ? '' : ' class="' . $classes . '"';

		$srcHtml = ' src="' . $banner->getBannerUrl($width, $height, $colour) . '"';
		$altHtml = ' alt="' . $width . 'x' . $height . ' banner"';

		$styleHtml = ' style="width: ' . $width . 'px; height: ' . $height . 'px;"';

		return '<div' . $divHtml . $classHtml . $styleHtml . '>' . PHP_EOL . "\t" .
			'<img' . $srcHtml . $altHtml . ' />' . PHP_EOL .
			'</div>';
	}
}
