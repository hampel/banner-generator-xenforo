<?php namespace Hampel\BannerGenerator\Cli\Command;

use Hampel\BannerGenerator\Option\DefaultColour;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateBanner extends Command
{
	protected function configure()
	{
		$this
			->setName('banner:create')
			->setDescription('Create a banner')
			->addOption(
				'width',
				'x',
				InputOption::VALUE_REQUIRED,
				"Width in pixels"
			)
			->addOption(
				'height',
				'y',
				InputOption::VALUE_REQUIRED,
				"Height in pixels"
			)
			->addOption(
				'colour',
				'c',
				InputOption::VALUE_OPTIONAL,
				"Colour"
			)
			->addOption(
				'force',
				'f',
				InputOption::VALUE_OPTIONAL,
				"Force - over-write existing banner"
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$width = intval($input->getOption('width'));
		if ($width <= 0)
		{
			$output->writeln("<error>Width must be specified</error>");
			return 1;
		}

		$height = intval($input->getOption('height'));
		if ($height <= 0)
		{
			$output->writeln("<error>Height must be specified</error>");
			return 1;
		}

		$banner = \XF::app()->get('banner');
		$colourKeys = $banner->getColourKeys();

		$colour = $input->getOption('colour');
		if (empty($colour))
		{
			$colour = DefaultColour::get();
		}
		elseif (!in_array($colour, $colourKeys))
		{
			$colourKeysList = implode(", ", $colourKeys);
			$output->writeln("<error>Colour must be one of [{$colourKeysList}]</error>");
			return 0;
		}

		$force = boolval($input->getOption('force'));

		$dest = $banner->generateBanner($width, $height, $colour, $force);

		if (empty($dest))
		{
			$output->writeln("Banner already exists");
		}
		else
		{
			$output->writeln("Banner created: [{$dest}]");
		}

		return 0;
	}
}