<?php

namespace eiriksm\BuildDrupalWasm;

use Composer\Command\BaseCommand;
use Composer\Factory;
use DrupalFinder\DrupalFinder;
use DrupalFinder\DrupalFinderComposerRuntime;
use Drush\DrupalFinder\DrushDrupalFinder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

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
      // First we need to append our own settings.php stuff to the settings.php
      // file. Technically, this could also be the default of course, but just
      // because we simply only support sqlite that's what we'll do.
      $contents = file_get_contents(__DIR__ . '/../assets/settings.append.php');
      $finder = new DrupalFinderComposerRuntime();
      $drupal_root = $finder->getComposerRoot();
      $file_path = sprintf('%s/%s/sites/default/settings.php', $composer_root, $variation);
      $existing_file = file_exists($file_path);
      if (!$existing_file) {
        throw new \Exception('We do not know where the settings.php file is');
      }
      $pre_contents = file_get_contents($file_path);
      try {
        file_put_contents($file_path, $contents, FILE_APPEND);
        $command = [
          'composer',
          'site-install',
        ];
        $process = new Process($command);
        $process->run();
        $code = $process->getExitCode();
        if (0 !== $code) {
          $output->writeln($process->getErrorOutput());
          $output->writeln($process->getOutput());
          throw new \Exception('The exit code of composer site-install was not 0, it was ' . $code);
        }
        return 0;
      } catch (\Throwable $e) {
        $output->writeln('There was an error: ' . $e->getMessage());
      }
      finally {
        file_put_contents($file_path, $pre_contents);
      }
  }

}
