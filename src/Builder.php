<?php

namespace eiriksm\BuildDrupalWasm;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Builder extends BaseCommand {

  /**
   * {@inheritDoc}
   */
  protected function configure()
  {
    $this->setName('build-drupal-wasm');
  }

  /**
   * {@inheritDoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
      return 0;
  }

}