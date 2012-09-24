Feature: Multipage HTML Formatter
  In order to browse large test suites
  As a feature writer
  I need to have a multipage html formatter with index

  Background:
    Given a file named "features/bootstrap/bootstrap.php" with:
      """
      <?php
      require_once 'PHPUnit/Autoload.php';
      require_once 'PHPUnit/Framework/Assert/Functions.php';
      """
    And a file named "features/bootstrap/FeatureContext.php" with:
      """
      <?php

      use Behat\Behat\Context\BehatContext;

      class FeatureContext extends BehatContext
      {
          private $value = 0;

          /**
           * @Given /I have entered (\d+)/
           */
          public function iHaveEntered($number) {
              $this->value = $number;
          }

          /**
           * @Then /I must have (\d+)/
           */
          public function iMustHave($number) {
              assertEquals($number, $this->value);
          }

          /**
           * @When /I (add|subtract) the value (\d+)/
           */
          public function iAddOrSubstractValue($operation, $number) {
              switch ($operation) {
                  case 'add':
                      $this->value += $number;
                      break;
                  case 'subtract':
                      $this->value -= $number;
                      break;
              }
          }
      }
      """

  Scenario: Creates multiple files
    Given a file named "features/World.feature" with:
      """
      Feature: World consistency
        In order to maintain stable behaviors
        As a features developer
        I want, that "World" flushes between scenarios

        Background:
          Given I have entered 10

        Scenario: Adding
          Then I must have 10
          And I add the value 6
          Then I must have 16
      """
    When I run "behat -f multi"
    Then it should pass
    And "index.html" should exist
    And "failures.html" should not exist
    And "pending.html" should not exist
    And "undefined.html" should not exist
    And "World.feature.html" should exist
