<?php namespace Hampel\BannerGenerator\XF\Template;

use Hampel\BannerGenerator\SubContainer\Banner;

class Templater extends XFCP_Templater
{
	public function fnBanner($templater, &$escape, $width, $height, $id = '', $colour = '')
	{
		/** @var Banner $banner */
		$banner = $this->app->get('banner');

		$banner->generateBanner($width, $height, $colour);

		$escape = false;

		$divId = '';
		if (!empty($id))
		{
			$divId = 'id="' . $id . '" ';
		}

		return '<div ' . $divId . ' style="width: ' . $width . 'px; height: ' . $height . 'px;">' . PHP_EOL . "\t" .
			'<img src="' . $banner->getBannerUrl($width, $height, $colour) . '" alt="' . $width . 'x' . $height . ' banner" />' . PHP_EOL .
			'</div>';
	}
}
