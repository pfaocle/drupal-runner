Drupal Runner
=============
An extension to [Robo](https://github.com/Codegyre/Robo) for building
[Drupal](https://www.drupal.org/) 7 sites.

**Disclaimer:** Drupal Runner is currently a proof of concept project and is
far from complete or stable. It also minimally requires Robo 0.4.4, but is
**currently incompatible with versions 0.4.6, 0.4.7 and any 0.5.x releases**.


Features
--------

* Define a Drupal 7 site build in YAML.
* Automate and repeat builds from scratch.
* Run Drush commands as part of the build process.


Installation
------------
Drupal sites intended to be built with Drupal Runner should be structured in a
particular way. Specifically:

* The root of the repository should be the contents of the site's subdirectory
  in Drupal's **sites/** folder.
* It _must_ contain a **composer.json** file defining (at least) Drupal Runner
  as a dependency.
* It _must_ contain a **RoboFile.php** files containing (at least) an empty
  `Robofile` class which extends `DrupalRunner`

You get can started quickly by referring to one of the full example builds
listed below. However, if you want to start from scratch:

1. Initialise a [Robo installation using Composer](https://github.com/Codegyre/Robo/blob/master/README.md#installing).
2. Create a **composer.json** file.
3. Add Add `https://bitbucket.org/pfaocle/drupal-runner.git` as a VCS repository
   in **composer.json**
4. Add `pfaocle/drupal-runner` as a dependency in **composer.json**
5. Run `composer install`
6. Edit **RoboFile.php** and extend the `DrupalRunner` class, instead of
   `\Robo\Tasks` (see the example below).
7. Create your **drupal.build.yml** configuration, or copy one of the examples
   and edit it to your needs.

### Example composer.json

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
            "pfaocle/drupal-runner": "dev-master#0.2.0"
        }
    }

### Example RoboFile.php

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


Build configuration
-------------------
A site's "build" is defined by the project's **drupal.build.yml** file.

All of the build configuration options are detailed in the example configuration
file **examples/commented.example.drupal.build.yml**


Usage
-----
Run `vendor/bin/robo list` to see the Drupal tasks available. To run a complete
build:

    # From your Drupal docroot:
    cd sites/your-site
    # This should now be the root of your repository.
    vendor/bin/robo drupal:magic ../..


Build examples
-------------------
There are a few build examples available in the **examples/** directory. Full
build examples, for reference or to add to, are also available on GitHub:

* [d7-drupal-runner-example](https://github.com/pfaocle/d7-drupal-runner-example) - a minimal, vanilla Drupal 7 build.
* [panopoly-drupal-runner-example](https://github.com/pfaocle/panopoly-drupal-runner-example) - Panopoly-based Drupal 7.
* [openpublic-drupal-runner-example](https://github.com/pfaocle/openpublic-drupal-runner-example) - OpenPublic-based Drupal 7 build.


Running tests
-------------
There is a [Codeception](http://codeception.com/) based test suite available, containing:

* a set of unit tests for various classes; and
* a "build" test suite which runs against a built copy of [dr7-drupal-runner-example](https://github.com/pfaocle/dr7-drupal-runner-example)

To run tests:

    # Ensure *Tester classes are built.
    codecept build

    # Run all unit tests and write out coverage report.
    codecept run unit --coverage-html

    # If the dr7 site is available locally, ensure tests/build.suite.yml is
    # configured correctly and run the "build" suite against it.
    codecept run build --env=local
