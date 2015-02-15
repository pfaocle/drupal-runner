<?php

use \BuildTester;

/**
 * Test results of the dr7 test migrations.
 */
class MigrateCest
{
    /**
     * Ensure expected Migrate-related modules have been enabled.
     *
     * @param \BuildTester $I
     *   The Tester object being used to test.
     */
    public function testExpectedModulesHaveBeenEnabled(BuildTester $I)
    {
        // A list of modules that Drupal Runner itself enables when the migrate step is present.
        $enforcedModules = ["migrate", "migrate_ui"];
        // A list of Migrate-related modules that have listed to be enabled in the dr7 build.
        // @todo Include "custom" migrate_dr7 module when added.
        $modulesInBuild  = ["migrate_d2d", "migrate_extras"];

        foreach (array_merge($enforcedModules, $modulesInBuild) as $module) {
            $I->seeInDatabase("system", ["name" => $module, "status" => 1]);
        }
    }

    /**
     * Ensure expected Drupal system variables have been set.
     *
     * Migrate steps are configured to set a variable defining the source directory of any physical files to migrate.
     * This is verified here, against expected values.
     *
     * @param \BuildTester $I
     *   The Tester object being used to test.
     */
    public function testMigrateFilesVariableHasBeenSet(BuildTester $I)
    {
        $I->seeInDatabase(
            "variable",
            [
                "name" => "dr7_file_migration",
                "value" => 's:40:"/var/www/vhosts/dr7-old.drupal.dev/files";',
            ]
        );
    }
}
