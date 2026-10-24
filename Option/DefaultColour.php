<?php namespace Hampel\BannerGenerator\Option;

use Hampel\BannerGenerator\SubContainer\Banner;
use XF\Option\AbstractOption;

class DefaultColour extends AbstractOption
{
	/**
	 * @param array<string, mixed> $htmlParams
	 *
	 * @return mixed what core's getSelectRow() returns, which it does not declare
	 */
	public static function renderOption(\XF\Entity\Option $option, array $htmlParams)
	{
		/** @var Banner $banner */
		$banner = \XF::app()->get('banner');

		return self::getSelectRow($option, $htmlParams, $banner->getColoursForSelect());
	}

	/**
	 * @return string|null
	 */
	public static function get()
	{
		return \XF::options()->hampelBannerGeneratorDefaultColour;
	}
}