<?php

use \BuildTester;

/**
 * Test parts of the actual Drupal 7 site built by the dr7 repo and Drupal Runner.
 */
class GeneratedBuildCest
{
    /**
     * The path to the dr7 site subdirectory, relative to the Codeception project root.
     *
     * We assume the tests are being run within Drupal Runner's vendor directory, underneath an installation of the dr7
     * test site, i.e. we are running something like:
     *
     *     cd /path/to/webroot/sites/dr7/vendor/pfaocle/drupal-runner
     *     ../../bin/codecept run build
     *
     * @see http://codeception.com/docs/modules/Filesystem
     */
    const SITES_SUBDIRECTORY_PATH = "../../..";

    /**
     * @var BuildTester
     *   Store the Tester object being used to test.
     */
    protected $tester;

    /**
     * Test that the files written to during build have the expected content.
     *
     * Tests the content of sites.php, settings.php
     *
     * @param BuildTester $I
     *   The Tester object being used to test.
     */
    public function testFilesWrittenDuringBuild(BuildTester $I)
    {
        $this->tester = $I;

        $this->fileContentsHelper(
            "sites.php",
            self::SITES_SUBDIRECTORY_PATH . DIRECTORY_SEPARATOR . "..",
            $this->sitesFileContents()
        );
        $this->fileContentsHelper(
            "settings.php",
            self::SITES_SUBDIRECTORY_PATH,
            $this->settingsFileLocalSettingsContent()
        );

    }

    /**
     * Regression/bug test for repeated inclusion of local settings in settings.php
     *
     * Fixed in commit f0a8fa8
     *
     * Prior to this fix, Drupal Runner would repeatedly append the "local settings" snippet of PHP to the settings.php
     * file for the build, if the file already existed and contained the PHP snippet from a previous build.
     *
     * @group bug
     *
     * @param BuildTester $I
     *   The Tester object being used to test.
     */
    public function testSettingsFileDoesntContainLocalSettingsSnippetMoreThanOnce(BuildTester $I)
    {
        $pathToSitesSubDirectory = "../../..";
        $I->openFile($pathToSitesSubDirectory . DIRECTORY_SEPARATOR . "settings.php");
        // Join two strings containing the local settings snippet together and ensure settings.php DOES NOT contain it.
        $content = $this->settingsFileLocalSettingsContent() . $this->settingsFileLocalSettingsContent();
        $I->dontSeeInThisFile($content);
    }

    /**
     * Helper to check for a given file, open it and check its contents.
     *
     * @param string $filename
     *   The file to open's filename.
     * @param string $path
     *   The file to open's path.
     * @param string $content
     *   A string to check for in the opened file.
     */
    protected function fileContentsHelper($filename, $path, $content)
    {
        $I = $this->tester;
        $I->expectTo("see a file $filename with the expected content");
        $I->seeFileFound($filename, $path);
        $I->openFile($path . DIRECTORY_SEPARATOR . $filename);
        $I->seeInThisFile($content);
    }

    /**
     * Define the expected contents of sites.php
     *
     * @return string
     *   The completed, expected contents of sites.php as written by the dr7 test site.
     */
    private function sitesFileContents()
    {
        $sitesFileContents = <<<EOS
<?php
  \$sites['8080.dr7.drupal.dev'] = 'dr7';
  \$sites['dr7.stage.example.com'] = 'dr7';
  \$sites['dr7.prod.example.com'] = 'dr7';
EOS;
        return $sitesFileContents;
    }

    /**
     * Define the expected contents of settings.php
     *
     * @return string
     *   A snippet of PHP used by Drupal Runner to include local settings.
     */
    private function settingsFileLocalSettingsContent()
    {
        $settingsFileLocalSettingsContent = <<<EOS
// Include environment specific settings.
if (file_exists(conf_path() . '/settings.local.php')) {
  include_once 'settings.local.php';
}

EOS;
        return $settingsFileLocalSettingsContent;
    }
}
