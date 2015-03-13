<?php

use \Robo\Drupal\DrupalSite;

/**
 * Test the DrupalSite class.
 */
class DrupalSiteTest extends \Codeception\TestCase\Test
{
    /**
     * Test expected public statics are available.
     */
    public function testExpectedPublicStaticVariablesAreAvailable()
    {
        $this->assertNotEmpty(DrupalSite::$drupalHiddenFiles);
        $this->assertNotEmpty(DrupalSite::$unwantedFilesPatterns);
        $this->assertNotEmpty(DrupalSite::$sitesFileLinePattern);
        $this->assertNotEmpty(DrupalSite::$drupalDefaultTheme);
    }
}
