<?php

use \BuildTester;

/**
 * Test parts of the actual Drupal 7 site built by the dr7 repo and Drupal Runner.
 */
class GeneratedBuildCest
{
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
        // @todo Sort this - we assume the tests are being run within Drupal
        // Runner's vendor directory, underneath an installation of the dr7
        // test site, i.e. we are running something like:
        //
        // cd /path/to/webroot/sites/dr7/vendor/pfaocle/drupal-runner
        // ../../bin/codecept run...
        $pathToSitesSubDirectory = "../../..";

        $this->tester = $I;
        $this->fileContentsHelper(
            "sites.php",
            $pathToSitesSubDirectory . DIRECTORY_SEPARATOR . "..",
            $this->sitesFileContents()
        );
        $this->fileContentsHelper(
            "settings.php",
            $pathToSitesSubDirectory,
            $this->settingsFileLocalSettingsContent()
        );

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
