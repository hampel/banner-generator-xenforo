<?php namespace Hampel\BannerGenerator\SubContainer;

use Hampel\BannerGenerator\Option\DefaultColour;
use Hampel\BannerGenerator\Option\SavePath;
use XF\SubContainer\AbstractSubContainer;
use XF\Util\File;

class Banner extends AbstractSubContainer
{
	public function initialize()
	{
//		$app = $this->app;
		$container = $this->container;

		$container['colours'] = function($c)
		{
			return [
				'black' => [
					'background' => [0, 0, 0],
					'text' => [255, 255, 255],
					'phrase' => 'hampel_bannergenerator_colour_black'
				],
				'red' => [
					'background' => [255, 0, 0],
					'text' => [255, 255, 255],
					'phrase' => 'hampel_bannergenerator_colour_red'
				],
				'green' => [
					'background' => [0, 255, 0],
					'text' => [0, 0, 0],
					'phrase' => 'hampel_bannergenerator_colour_green'
				],
				'blue' => [
					'background' => [0, 0, 255],
					'text' => [255, 255, 255],
					'phrase' => 'hampel_bannergenerator_colour_blue'
				],
				'yellow' => [
					'background' => [255, 255, 0],
					'text' => [0, 0, 0],
					'phrase' => 'hampel_bannergenerator_colour_yellow'
				],
				'cyan' => [
					'background' => [0, 255, 255],
					'text' => [0, 0, 0],
					'phrase' => 'hampel_bannergenerator_colour_cyan'
				],
				'magenta' => [
					'background' => [255, 0, 255],
					'text' => [0, 0, 0],
					'phrase' => 'hampel_bannergenerator_colour_magenta'
				],
				'grey' => [
					'background' => [127, 127, 127],
					'text' => [255, 255, 255],
					'phrase' => 'hampel_bannergenerator_colour_grey'
				],
				'white' => [
					'background' => [255, 255, 255],
					'text' => [0, 0, 0],
					'phrase' => 'hampel_bannergenerator_colour_white'
				],
			];
		};
	}

	public function generateBanner($width, $height, $colourKey = '', $force = false)
	{
		if (empty($colourKey))
		{
			$colourKey = DefaultColour::get();
		}

		if (!$colour = $this->getColour($colourKey))
		{
			\XF::logError("Invalid colour specified for banner test: {$colourKey}");
			return;
		}

		$fs = $this->app->fs();

		$destImage = $this->getAbstractedDataPath($width, $height, $colourKey);

		if ($fs->has($destImage) && !$force)
		{
			return ""; // already have a banner generated
		}

		return $this->_generateBanner($width, $height, $colour, $destImage);
	}

	protected function _generateBanner($width, $height, array $colour, $dest)
	{
		$im = imagecreate($width, $height);
		if ($im === false)
		{
			\XF::logError("Could not generate image for banner test - imagecreate returned false");
			return;
		}

		$background_color = imagecolorallocate($im, $colour['background'][0], $colour['background'][1], $colour['background'][2]);
		$text_color = imagecolorallocate($im, $colour['text'][0], $colour['text'][1], $colour['text'][2]);
		imagestring($im, 5, 5, 5,  sprintf("%dx%d", $width, $height), $text_color);

		$tempFile = File::getTempFile();

		imagepng($im, $tempFile);
		imagedestroy($im);

		File::copyFileToAbstractedPath($tempFile, $dest);

		return $dest;
	}

	public function getAbstractedDataPath($width, $height, $colour)
	{
		return sprintf('data://%s/%dx%d-%s.png', SavePath::get(), $width, $height, $colour);
	}

	public function getBannerUrl($width, $height, $colour)
	{
		return \XF::app()->applyExternalDataUrl(sprintf('%s/%dx%d-%s.png', SavePath::get(), $width, $height, $colour));
	}

	public function getColours()
	{
		return $this->container['colours'];
	}

	public function getColoursForSelect()
	{
		$choices = [];
		foreach ($this->getColours() as $key => $colour)
		{
			$choices[$key] = \XF::phrase($colour['phrase']);
		}

		return $choices;
	}

	public function getColourKeys()
	{
		return array_keys($this->container['colours']);
	}

	public function getColour($colour)
	{
		return $this->container['colours'][$colour] ?? null;
	}
}
