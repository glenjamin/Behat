Feature: Step Arguments
  In order to write extended steps
  As a feature writer
  I need ability to specify Table & PyString arguments to steps

  Background:
    Given a file named "features/support/bootstrap.php" with:
      """
      <?php
      require_once 'PHPUnit/Autoload.php';
      require_once 'PHPUnit/Framework/Assert/Functions.php';
      """
    And a file named "features/support/env.php" with:
      """
      <?php
      $this->strings[1] = "hello,\n  w\n   o\nr\nl\n   d";
      $this->tables[1]  = array(
        array('item1' => 'super', 'item2' => 'mega', 'item3' => 'extra'),
        array('item1' => 'hyper', 'item2' => 'mini', 'item3' => 'XXL'),
      );
      """
    And a file named "features/steps/arguments.php" with:
      """
      <?php
      $steps->Given('/^a pystring:$/', function($world, $string) {
          $world->input = $string;
      });
      $steps->Given('/^a table:$/', function($world, $table) {
          $world->input = $table;
      });
      $steps->Then('/^it must be equals to string (\d+)$/', function($world, $arg1) {
          assertEquals($world->strings[intval($arg1)], (string) $world->input);
      });
      $steps->Then('/^it must be equals to table (\d+)$/', function($world, $arg1) {
          assertEquals($world->tables[intval($arg1)], $world->input->getHash());
      });
      """

  Scenario: PyStrings
    Given a file named "features/pystring.feature" with:
      """
      Feature: PyStrings
        Scenario:
          Given a pystring:
            '''
            hello,
              w
               o
          r
           l
               d
            '''
          Then it must be equals to string 1
      """
    When I run "behat -f progress features/pystring.feature"
    Then it should pass with:
      """
      ..
      
      1 scenario (1 passed)
      2 steps (2 passed)
      """

  Scenario: PyStrings tokens
    Given a file named "features/pystring_tokens.feature" with:
      """
      Feature: PyStrings
        Scenario Outline:
          Given a pystring:
            '''
            <word1>
              w
               o
          r
           <word2>
               d
            '''
          Then it must be equals to string 1

          Examples:
            | word1  | word2 |
            | hello, | l     |
      """
    When I run "behat -f progress features/pystring_tokens.feature"
    Then it should pass with:
      """
      ..
      
      1 scenario (1 passed)
      2 steps (2 passed)
      """

  Scenario: Table tokens
    Given a file named "features/table_tokens.feature" with:
      """
      Feature: Tables
        Scenario Outline:
          Given a table:
            | item1   | item2   | item3   |
            | <word1> | <word3> | extra   |
            | hyper   | mini    | <word2> |
          Then it must be equals to table 1

          Examples:
            | word1 | word2 | word3 |
            | super | XXL   | mega  |
      """
    When I run "behat -f progress features/table_tokens.feature"
    Then it should pass with:
      """
      ..

      1 scenario (1 passed)
      2 steps (2 passed)
      """

  Scenario: Table
    Given a file named "features/table.feature" with:
      """
      Feature: Tables
        Scenario:
          Given a table:
            | item1 | item2 | item3 |
            | super | mega  | extra |
            | hyper | mini  | XXL   |
          Then it must be equals to table 1
      """
    When I run "behat -f progress features/table.feature"
    Then it should pass with:
      """
      ..

      1 scenario (1 passed)
      2 steps (2 passed)
      """

  Scenario: Named arguments
    Given a file named "features/steps/named_args_steps.php" with:
      """
      <?php
      $steps->Given('/^I have number2 = (?P<number2>\d+) and number1 = (?P<number1>\d+)$/', function($world, $number1, $number2) {
          assertEquals(13, $number1);
          assertEquals(243, $number2);
      });
      """
    And a file named "features/named_args.feature" with:
      """
      Feature: Named arguments
        In order to maintain i18n for steps
        As a step developer
        I need to be able to declare regex with named parameters

        Scenario:
          Given I have number2 = 243 and number1 = 13
      """
    When I run "behat -f progress features/named_args.feature"
    Then it should pass with:
      """
      .

      1 scenario (1 passed)
      1 step (1 passed)
      """
