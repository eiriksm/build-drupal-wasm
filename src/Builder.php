<?php

namespace eiriksm\BuildDrupalWasm;

use Composer\Command\BaseCommand;
use Composer\Factory;
use DrupalFinder\DrupalFinder;
use DrupalFinder\DrupalFinderComposerRuntime;
use Drush\DrupalFinder\DrushDrupalFinder;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Internal\Path\Service\PathHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Builder extends BaseCommand {

  public function __construct(?string $name = NULL) {
    parent::__construct($name);
    $path_helper = new PathHelper();
    $this->pathFactory = new PathFactory($path_helper);
  }

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
      $output->writeln('<info>Found an existing settings.php file and will append to it, to install a new site based on it</info>');
      $pre_contents = file_get_contents($file_path);
      $return = 1;
      try {
        $active_directory = $this->pathFactory->create($drupal_root);
        // Create a staging directory.
        $staging_path = sys_get_temp_dir() . '/composer-stager/' . hash('sha256', $drupal_root);
        $staging_directory = $this->pathFactory->create($staging_path);
        file_put_contents($file_path, $contents, FILE_APPEND);
        $output->writeln('<info>The required settings were added to the existing settings.php</info>');
        $this->runComposerSiteInstall($output);
        $output->writeln('<info>Composer site-install completed</info>');
        // Let's apply some patches.
        $output->writeln('<info>Applying patches</info>');
        $this->applyPatches($output, $finder->getDrupalRoot());
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
        $this->placeAllFilesFromPublic($finder->getComposerRoot());
        $return = 0;
      } catch (\Throwable $e) {
        $output->writeln($process->getErrorOutput());
        $output->writeln($process->getOutput());
        $output->writeln('<error>There was an error: ' . $e->getMessage() . '</error>');
      }
      finally {
        file_put_contents($file_path, $pre_contents);
      }
      return $return;
  }

  public function applyPatches(OutputInterface $output, string $composer_root)
  {
    $process = Process::fromShellCommandline('patch -p1 ' . __DIR__ . '/../patches/renderer-remove-fibers.patch');
    $process->run();
    $output->writeln($process->getErrorOutput());
    $output->writeln($process->getOutput());
    if (0 !== $process->getExitCode()) {
      throw new \Exception('The exit code of the patch command was not 0, it was ' . $process->getExitCode());
    }
  }

  public function runComposerSiteInstall(OutputInterface $output) {
    $command = [
      'composer',
      'site-install',
    ];
    $process = new Process($command);
    $output->writeln('<info>Running composer site-install</info>');
    $process->run();
    $output->writeln('<info>Composer site-install completed</info>');
    $code = $process->getExitCode();
    if (0 !== $code) {
      $output->writeln($process->getErrorOutput());
      $output->writeln($process->getOutput());
      throw new \Exception('The exit code of composer site-install was not 0, it was ' . $code);
    }
  }

  public function placeAllFilesFromPublic($composer_root) {
    // First place all files from the folder assets/public into an array.
    $files = [];
    $directory = __DIR__ . '/../assets/public';
    $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory), \RecursiveIteratorIterator::SELF_FIRST);
    foreach($objects as $name => $object){
      if (is_file($name)) {
        $files[] = $name;
      }
    }
    // Now place them inside of public.
    $public = $composer_root . '/public';
    foreach ($files as $file) {
      $target = $public . '/' . str_replace($directory . '/', '', $file);
      $target_dir = dirname($target);
      if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, TRUE);
      }
      copy($file, $target);
    }
  }

}
