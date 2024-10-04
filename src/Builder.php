<?php

namespace eiriksm\BuildDrupalWasm;

use Composer\Command\BaseCommand;
use Composer\Factory;
use DrupalFinder\DrupalFinder;
use DrupalFinder\DrupalFinderComposerRuntime;
use Drush\DrupalFinder\DrushDrupalFinder;
use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Core\CleanerInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Core\Beginner;
use PhpTuf\ComposerStager\Internal\Core\Cleaner;
use PhpTuf\ComposerStager\Internal\Environment\Service\Environment;
use PhpTuf\ComposerStager\Internal\FileSyncer\Service\FileSyncer;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem;
use PhpTuf\ComposerStager\Internal\Finder\Service\ExecutableFinder;
use PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathListFactory;
use PhpTuf\ComposerStager\Internal\Path\Service\PathHelper;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveAndStagingDirsAreDifferent;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveDirExists;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveDirIsReady;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveDirIsWritable;
use PhpTuf\ComposerStager\Internal\Precondition\Service\BeginnerPreconditions;
use PhpTuf\ComposerStager\Internal\Precondition\Service\CleanerPreconditions;
use PhpTuf\ComposerStager\Internal\Precondition\Service\CommonPreconditions;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ComposerIsAvailable;
use PhpTuf\ComposerStager\Internal\Precondition\Service\HostSupportsRunningProcesses;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoAbsoluteSymlinksExist;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoNestingOnWindows;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointOutsideTheCodebase;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoUnsupportedLinksExist;
use PhpTuf\ComposerStager\Internal\Precondition\Service\RsyncIsAvailable;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirDoesNotExist;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirExists;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirIsReady;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirIsWritable;
use PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactory;
use PhpTuf\ComposerStager\Internal\Process\Factory\SymfonyProcessFactory;
use PhpTuf\ComposerStager\Internal\Process\Service\ComposerProcessRunner;
use PhpTuf\ComposerStager\Internal\Process\Service\OutputCallback;
use PhpTuf\ComposerStager\Internal\Process\Service\RsyncProcessRunner;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactory;
use PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions;
use PhpTuf\ComposerStager\Internal\Translation\Service\LocaleOptions;
use PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxy;
use PhpTuf\ComposerStager\Internal\Translation\Service\Translator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Builder extends BaseCommand {

  protected PathFactoryInterface $pathFactory;
  protected BeginnerInterface $beginner;
  protected CleanerInterface $cleaner;

  public function __construct(?string $name = NULL) {
    parent::__construct($name);
    $path_helper = new PathHelper();
    $this->pathFactory = new PathFactory($path_helper);
    $environment = new Environment();
    $domain_options = new DomainOptions();
    $locale_options = new LocaleOptions();
    $symfony_translator_proxy = new SymfonyTranslatorProxy();
    $translatable_factory = new TranslatableFactory($domain_options, new Translator($domain_options, $locale_options, $symfony_translator_proxy));
    $finder = new ExecutableFinder(new \Symfony\Component\Process\ExecutableFinder(), $translatable_factory);
    $symfony_file_system = new \Symfony\Component\Filesystem\Filesystem();
    $file_system = new Filesystem($environment, $this->pathFactory, $symfony_file_system, $translatable_factory);
    $path_list_factory = new PathListFactory($path_helper);
    $symfony_process_factory = new SymfonyProcessFactory($translatable_factory);
    $process_factory = new ProcessFactory($symfony_process_factory, $translatable_factory);
    $rsync_process_runner = new RsyncProcessRunner($finder, $process_factory, $translatable_factory);
    $file_syncer = new FileSyncer($environment, $finder, $file_system, $this->pathFactory, $path_list_factory, $rsync_process_runner, $translatable_factory);
    $active_and_staging = new ActiveAndStagingDirsAreDifferent($environment, $translatable_factory);
    $active_dir_exists = new ActiveDirExists($environment, $file_system, $translatable_factory);
    $active_dir_is_writable = new ActiveDirIsWritable($environment, $file_system, $translatable_factory);
    $active_dir_is_ready = new ActiveDirIsReady($environment, $active_dir_exists, $active_dir_is_writable, $translatable_factory);
    $composer_process_runner = new ComposerProcessRunner($finder, $process_factory, $translatable_factory);
    $output_callback = new OutputCallback();
    $composer_available = new ComposerIsAvailable($composer_process_runner, $environment, $finder, $output_callback, $translatable_factory);
    $host_supports_running = new HostSupportsRunningProcesses($environment, $process_factory, $translatable_factory);
    $no_nesting_on_windows = new NoNestingOnWindows($environment, $path_helper, $translatable_factory);
    $rsync_is_available = new RsyncIsAvailable($environment, $finder, $process_factory, $translatable_factory);
    $common_preconditions = new CommonPreconditions($environment, $translatable_factory, $active_and_staging, $active_dir_is_ready, $composer_available, $host_supports_running, $no_nesting_on_windows, $rsync_is_available);
    $file_finder = new FileFinder($this->pathFactory, $path_helper, $path_list_factory, $translatable_factory);
    $no_abs_sym = new NoAbsoluteSymlinksExist($environment, $file_finder, $file_system, $this->pathFactory, $path_list_factory, $translatable_factory);
    $no_hard_links = new NoHardLinksExist($environment, $file_finder, $file_system, $this->pathFactory, $path_list_factory, $translatable_factory);
    $no_links_windows = new NoLinksExistOnWindows($environment, $file_finder, $file_system, $this->pathFactory, $path_list_factory, $translatable_factory);
    $no_sym_outside = new NoSymlinksPointOutsideTheCodebase($environment, $file_finder, $file_system, $this->pathFactory, $path_helper, $path_list_factory, $translatable_factory);
    $no_unsupported_links = new NoUnsupportedLinksExist($environment, $translatable_factory, $no_abs_sym, $no_hard_links, $no_links_windows, $no_sym_outside);
    $staging_not_exists = new StagingDirDoesNotExist($environment, $file_system, $translatable_factory);
    $beginner_precondition = new BeginnerPreconditions($environment, $common_preconditions, $no_unsupported_links, $staging_not_exists, $translatable_factory);
    $this->beginner = new Beginner($file_syncer, $beginner_precondition, $beginner_precondition);
    $staging_dir_exists = new StagingDirExists($environment, $file_system, $translatable_factory);
    $staging_dir_writable = new StagingDirIsWritable($environment, $file_system, $translatable_factory);
    $staging_dir_is_ready = new StagingDirIsReady($environment, $translatable_factory, $staging_dir_exists, $staging_dir_writable);
    $cleaner_precondtitions = new CleanerPreconditions($environment, $common_preconditions, $staging_dir_is_ready, $translatable_factory);
    $this->cleaner = new Cleaner($file_system, $cleaner_precondtitions, $cleaner_precondtitions);
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
        $active_directory = $this->pathFactory->create($finder->getComposerRoot());
        // Create a staging directory.
        $staging_path = sys_get_temp_dir() . '/composer-stager/' . hash('sha256', $finder->getComposerRoot());
        $staging_directory = $this->pathFactory->create($staging_path);
        // Get the diff of the composer root and the drupal root, since this
        // will be the "web" folder. The diff here refers to the difference of
        // the strings.
        $diff = str_replace($finder->getComposerRoot(), '', $finder->getDrupalRoot());
        $this->beginner->begin($active_directory, $staging_directory);
        $drupal_root_in_stage = sprintf('%s%s', $staging_path, $diff);
        $path_in_stage = sprintf('%s/sites/default/settings.php', $drupal_root_in_stage);
        file_put_contents($path_in_stage, $contents, FILE_APPEND);
        $output->writeln('<info>The required settings were added to the existing settings.php</info>');
        $this->runComposerSiteInstall($output, $staging_directory);
        // Let's apply some patches.
        $output->writeln('<info>Applying patches</info>');
        $this->applyPatches($output, $drupal_root_in_stage);
        $this->archiveAndPlace($output, $staging_directory, $finder);
        $return = 0;
      } catch (\Throwable $e) {
        $output->writeln('<error>There was an error: ' . $e->getMessage() . '</error>');
      }
      $output->writeln('<info>Cleaning up</info>');
      $this->cleaner->clean($active_directory, $staging_directory);
      return $return;
  }

  private function archiveAndPlace(OutputInterface $output, PathInterface $staging_dir, DrupalFinderComposerRuntime $finder)
  {
    // Now let's dump it as an archive.
    $command = [
      'composer',
      'archive',
      '--dir=.',
      '--file=public/assets/build',
      '--format=zip',
    ];
    $process = new Process($command, $staging_dir->absolute());
    $output->writeln('Running composer archive');
    $process->run();
    $code = $process->getExitCode();
    if (0 != $code) {
      throw new \Exception('The exit code of the composer archive command was not 0, it was ' . $code);
    }
    $this->placeAllFilesFromPublic($finder->getComposerRoot());
    // Now also place the archive in the public folder.
    $public = $finder->getComposerRoot() . '/public';
    $target = $public . '/assets/build.zip';
    rename($staging_dir->absolute() . '/public/assets/build.zip', $target);
  }

  public function applyPatches(OutputInterface $output, string $drupal_root_in_stage)
  {
    $process = Process::fromShellCommandline('patch -p1 < ' . __DIR__ . '/../patches/renderer-remove-fibers.patch', $drupal_root_in_stage);
    $process->run();
    $output->writeln($process->getErrorOutput());
    $output->writeln($process->getOutput());
    if (0 !== $process->getExitCode()) {
      throw new \Exception('The exit code of the patch command was not 0, it was ' . $process->getExitCode());
    }
  }

  public function runComposerSiteInstall(OutputInterface $output, PathInterface $staging_directory) {
    $command = [
      'composer',
      'site-install',
    ];
    $process = new Process($command, $staging_directory->absolute());
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
