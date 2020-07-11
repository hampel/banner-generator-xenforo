<?php namespace Hampel\BannerGenerator\XF\Template;

class Templater extends XFCP_Templater
{
	public function fnBanner($templater, &$escape, $sizes, $id = '')
	{
		if (!is_array($sizes))
		{
			$sizes = array($sizes);
		}

		$key = array_rand($sizes);

		$size = $sizes[$key];

		$bannerPath = $this->getAbstractedBannerPath($size);

		$fs = $this->app->fs();

		if (!$fs->has($bannerPath))
		{
			return "[Banner not found: {$size}]";
		}

		$escape = false;

		$divId = '';
		if (!empty($id))
		{
			$divId = 'id="' . $id . '" ';
		}

		return '<div ' . $divId . '><img src="' . $this->getBannerUrl($size) . '" alt="' . $size . ' banner" /></div>';
	}

	public function getAbstractedBannerPath($size)
	{
		return sprintf('data://banner-test/%s.png', $size);
	}

	public function getBannerUrl($size)
	{
		return \XF::app()->applyExternalDataUrl(sprintf('banner-test/%s.png', $size));
	}
}
