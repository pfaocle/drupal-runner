Drupal Runner CHANGELOG
=======================

0.3.x - Configuration
---------------------
* [BUG] Do not append the 'local settings inclusion' PHP snippet in
  settings.php if it's already there, e.g. from a previous build.
* Collapse pre and post steps modules and commands into simply a list of
  commands. Modules can still be enabled with by defining a command which calls
  "pm-enable" (or its alias, "en").
* Add php-lint as a pre-commit check.
* Provide a new functional site suite called "build" which will test a built
  dr7 test site hosted on a Druphpet-based VM.
* Update the build configuration system to use symfony/config

0.2.0 - Drush make command-line options
---------------------------------------
* Allow the build.yml to specify make-options to pass to drush make command
  line, by @dopey
* Add make-path to the 'complete example' configuration file.
* Character '@' cannot start any token in Yaml.
* Update Markdown files to stick to the Markdown Style Guide.

0.1.0 - Initial development release
-----------------------------------
* Functional, automated Drupal 7 site building.
* Supports minimal and other profiles (e.g. Panopoly).
* Build up Drupal sites from just code using Features.
* Custom configuration system for build files (to be deprecated/removed).
* Very minimal unit test suite with Codeception.
* Compatible with Robo 0.4.4 and 0.4.5 ONLY.
