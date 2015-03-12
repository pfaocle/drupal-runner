<?php

use \BuildTester;

/**
 * Test results of the dr7 test migrations.
 */
class MigrateCest
{
    /**
     * @var BuildTester
     *   Store the Tester object being used to test.
     */
    protected $tester;

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
        $modulesInBuild  = ["migrate_d2d", "migrate_extras", "migrate_example"];

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

    /**
     * Check the database for some content that should have been migrated.
     *
     * This data is subject to changes in the migrate_extras module provided with Migrate.
     *
     * @see http://cgit.drupalcode.org/migrate/tree/migrate_example
     *
     * @param \BuildTester $I
     *   The Tester object being used to test.
     */
    public function testExampleMigrationsHaveRun(BuildTester $I)
    {
        $this->tester = $I;

        $this->migrationContentHelper("role", ["Taster", "Vintner"]);

        $this->migrationContentHelper(
            "users",
            ["alice", "alice_2", "bob", "charlie", "darren", "emily", "fonzie"]
        );

        $this->migrationContentHelper(
            "node_type",
            ["migrate_example_beer", "migrate_example_producer", "migrate_example_wine"],
            "type"
        );

        $this->migrationContentHelper(
            "taxonomy_vocabulary",
            [
                "Migrate Example Beer Styles",
                "Migrate Example Wine Best With",
                "Migrate Example Wine Regions",
                "Migrate Example Wine Varieties",
            ]
        );

        $this->migrationContentHelper(
            "node",
            ["Archeo", "Boddington", "Boston Winery", "Heineken", "Miller Lite", "Montes"],
            "title"
        );
    }

    /**
     * Helper for testing some Drupal tables for migrated content.
     *
     * @param string $table
     *   Name of the Drupal database table.
     * @param array $items
     *   A non-associative array of item values to check for.
     * @param string $key
     *   The column name to check against.
     */
    protected function migrationContentHelper($table, $items, $key = "name")
    {
        $I = $this->tester;
        foreach ($items as $item) {
            $I->seeInDatabase($table, [$key => $item]);
        }
    }
}
