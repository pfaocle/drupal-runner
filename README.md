Drupal Runner
=============
An extension to [Robo](https://github.com/Codegyre/Robo) for building Drupal.

Installing with Composer
------------------------
1. Initialise your [Robo installation using Composer](https://github.com/Codegyre/Robo/blob/master/README.md#installing).
2. Add `https://bitbucket.org/pfaocle/drupal-runner.git` to `composer.json` as
   a VCS repository.
3. Add `"pfaocle/drupal-runner": "dev-master"` to `composer.json`.
4. Run `composer update`.

### Example

    {
        "name": "pfaocle/robo-drupal-testing",
        "description": "Testing set-up for Drupal Runner.",

        "repositories": [
            {
                "type": "vcs",
                "url": "https://bitbucket.org/pfaocle/drupal-runner.git"
            }
        ],

        "require": {
            "pfaocle/drupal-runner": "dev-master"
        }
    }

Note that Drupal Runner minimally requires Robo 0.4.4, but is **currently
incompatible with versions 0.4.6, 0.4.7 and any 0.5.x releases**.

Configuration
-------------
All of the build configuration options are detailed in the example configuration
file `examples/commented.example.drupal.build.yml`.

Usage
-----
1. Edit `RoboFile.php` and extend the `DrupalRunner` class, instead of
   `\Robo\Tasks` (see the example below).
2. Run `vendor/bin/robo list` to see the Drupal tasks available.

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

More build examples
-------------------
There are a few build examples available in the `examples` directory. Full
build examples, for reference or to add to, are also available on GitHub:

* [d7-drupal-runner-example](https://github.com/pfaocle/d7-drupal-runner-example) - a minimal, vanilla Drupal 7 build.
* [panopoly-drupal-runner-example](https://github.com/pfaocle/panopoly-drupal-runner-example) - Panopoly-based Drupal 7.
* [openpublic-drupal-runner-example](https://github.com/pfaocle/openpublic-drupal-runner-example) - OpenPublic-based Drupal 7 build.
