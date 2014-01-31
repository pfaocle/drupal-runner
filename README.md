Drupal Runner
====

An extension to [Robo](https://github.com/Codegyre/Robo) for building Drupal.


## Installing

### Composer

1. Initialise your [RoboFile](https://github.com/Codegyre/Robo/blob/master/README.md#installing) using Composer.
2. Add https://bitbucket.org/pfaocle/drupal-runner.git to `composer.json` as a VCS repository.
3. Add `"pfaocle": "dev-master"` to `composer.json`.
4. Run `composer update`.


## Configuration

Check the `example.drupal.build.yml` file for more details. You will need to copy this file to `drupal.build.yml` in your Robo installation directory in order to use it.


## Usage

1. Edit `RoboFile.php` and extend the `DrupalRunner` class, instead of `\Robo\Tasks` (see the example below).
2. Run `vendor\bin\robo list` to see the Drupal tasks available.

### Example

    <?php
    /**
     * @file
     * Robo builder for my Drupal site.
     */

    require 'vendor/autoload.php';

    use Robo\DrupalRunner;

    class Robofile extends DrupalRunner
    {
    }
