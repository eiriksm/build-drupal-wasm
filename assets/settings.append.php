$databases['default']['default'] = array (
  'database' => DRUPAL_ROOT . '/../database.sqlite',
  'prefix' => '',
  'driver' => 'sqlite',
  'namespace' => 'Drupal\\sqlite\\Driver\\Database\\sqlite',
  'autoload' => 'core/modules/sqlite/src/Driver/Database/sqlite/',
);
$settings['skip_permissions_hardening'] = TRUE;
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;
$config['system.logging']['error_level'] = 'verbose';
$settings['hash_salt'] = 'a95f869b07e545114d46ae9132d1871eee9ad706d921e8e24930282bbc69152d';

