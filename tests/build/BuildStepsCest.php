<?php

use \BuildTester;

/**
 * Verify various build steps have been run.
 */
class BuildStepsCest
{
    /**
     * Ensure the defined pre and post steps have been run.
     *
     * @param \BuildTester $I
     *   The Tester object being used to test.
     */
    public function testPreAndPostStepsHaveRun(BuildTester $I)
    {
        // Verify the defined pre step.
        $I->seeInDatabase("variable", ["name" => "site_mail", "value" => "noreply@example.com"]);

        // Verify the defined post step.
        $I->seeInDatabase(
            "block",
            ["module" => "user", "delta" => "new", "region" => "sidebar_first", "status" => 1]
        );
        $I->seeInDatabase(
            "block",
            ["module" => "user", "delta" => "online", "region" => "sidebar_first", "status" => 1]
        );
    }
}
