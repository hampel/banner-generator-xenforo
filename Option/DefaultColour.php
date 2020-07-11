<?php namespace Hampel\BannerGenerator\Option;

use Hampel\BannerGenerator\SubContainer\Banner;
use XF\Option\AbstractOption;

class DefaultColour extends AbstractOption
{
	public static function renderOption(\XF\Entity\Option $option, array $htmlParams)
	{
		/** @var Banner $banner */
		$banner = \XF::app()->get('banner');

		return self::getSelectRow($option, $htmlParams, $banner->getColoursForSelect());
	}

	public static function get()
	{
		return \XF::options()->hampelBannerGeneratorDefaultColour;
	}
}