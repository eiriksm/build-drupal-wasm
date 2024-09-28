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
      $drupal_root = $finder->getDrupalRoot();
      $file_path = sprintf('%s/sites/default/settings.php', $drupal_root);
      $existing_file = file_exists($file_path);
      if (!$existing_file) {
        throw new \Exception('We do not know where the settings.php file is');
      }
      $output->writeln('Found an existing settings.php file and will append to it, to install a new site based on it');
      $pre_contents = file_get_contents($file_path);
      $return = 1;
      try {
        file_put_contents($file_path, $contents, FILE_APPEND);
        $output->writeln('The required settings were added to the existing settings.php');
        $command = [
          'composer',
          'site-install',
        ];
        $process = new Process($command);
        $output->writeln('Running composer site-install');
        $process->run();
        $output->writeln('Composer command completed');
        $code = $process->getExitCode();
        if (0 !== $code) {
          $output->writeln($process->getErrorOutput());
          $output->writeln($process->getOutput());
          throw new \Exception('The exit code of composer site-install was not 0, it was ' . $code);
        }
        // Now let's dump it as an archive.
        $command = [
          'composer',
          'archive',
          '--dir=.',
          '--file=public/assets/build',
          '--format=zip',
        ];
        $process = new Process($command);
        $output->writeln('Running composer archive');
        $process->run();
        $code = $process->getExitCode();
        if (0 != $code) {
          throw new \Exception('The exit code of the composer archive command was not 0, it was ' . $code);
        }
        $return = 0;
      } catch (\Throwable $e) {
        $output->writeln($process->getErrorOutput());
        $output->writeln($process->getOutput());
        $output->writeln('There was an error: ' . $e->getMessage());
      }
      finally {
        file_put_contents($file_path, $pre_contents);
      }
      return $return;
  }

}
