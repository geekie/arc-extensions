<?php

require_once dirname(__DIR__)."/pkgManager.php";

final class JestTestEngine extends BaseGeekieTestEngine {

  private $outputFile;

  protected function supportsRunAllTests() {
    return true;
  }

  final public function shouldEchoTestResults() {
    return false;
  }

  public function run() {
    $this->outputFile = new TempFile();

    $paths = $this->getPaths();
    $options = array();

    if (count($paths) == 0 && !$this->getRunAllTests()) {
      return array();
    }

    if (count($paths) > 0) {
      $options[] = csprintf('--findRelatedTests %Ls', $paths);
    }

    return $this->runTests($options);
  }

  private function runTests(array $options) {
    $console = PhutilConsole::getConsole();
    $options = implode(' ', array_merge(
      $options,
      array(
        '--colors',
        '--passWithNoTests true',
        '--json',
        '--outputFile=' . $this->outputFile
      )
    ));

    return $this->getResult($this->resolveFuture(new ExecFuture("yarn -s jest {$options}")));
  }

  private function getResult($future) {
    $future->resolve();

    $raw_results = phutil_json_decode(Filesystem::readFile($this->outputFile))['testResults'];

    $results = array();

    foreach ($raw_results as $result) {
      $test_result = new ArcanistUnitTestResult();
      $test_result->setName($result['name']);

      $test_result->setResult(idx($result, 'status', 'failed') == 'passed'
        ? ArcanistUnitTestResult::RESULT_PASS
        : ArcanistUnitTestResult::RESULT_FAIL);

      $test_result->setDuration(($result['endTime'] - $result['startTime']) / 1000);
      $test_result->setUserData($result['message']);

      $results[] = $test_result;
    }

    return $results;
  }

}
