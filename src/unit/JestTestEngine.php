<?php

final class JestTestEngine extends ArcanistUnitTestEngine {

  private $jest_binary;
  private $project_root;
  private $output_file;

  protected function supportsRunAllTests() {
    return true;
  }

  public function run() {
    $this->project_root = $this->getWorkingCopy()->getProjectRoot();
    $this->jest_binary = $this->getJestBinary();

    $paths = $this->getPaths();
    $options = array();

    // TODO(duailibe, sherman) find a clean way to solve this
    $paths[] = "packages/codesharing/symlinksChecker.test.js";
    foreach ($paths as $path) {
      $absolute_web_path = str_replace(
        '/rn/',
        '/web/',
        Filesystem::resolvePath($path, $this->project_root)
      );
      if (Filesystem::pathExists($absolute_web_path) && is_link($absolute_web_path)) {
        $paths[] = $absolute_web_path;
      }
    }

    if (!$this->getRunAllTests()) {
      $options[] = csprintf('--findRelatedTests %Ls', $paths);
    }

    $this->output_file = new TempFile();

    return $this->runTests($options);
  }

  private function runTests(array $options) {
    $console = PhutilConsole::getConsole();
    $options = implode(' ', array_merge(
      $options,
      array(
        '--silent',
        '--colors',
        '--json',
        '--reporters ' . __DIR__ . '/jest-summary-reporter.js',
        '--outputFile=' . $this->output_file
      )
    ));

    $future = new ExecFuture("{$this->jest_binary} {$options}");

    do {
      $done = $future->resolve(0.2);
      list(, $stderr) = $future->read();
      $console->writeErr("%s", $stderr);
      $future->discardBuffers();
    } while (!$done);

    return $this->getResult($future);
  }

  private function getResult($future) {
    $future->resolve();
    $raw_results = phutil_json_decode(Filesystem::readFile(
      $this->output_file))['testResults'];

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

  private function getJestBinary() {
    list($npm_bin, $err) = execx('npm bin');
    return Filesystem::resolvePath(
      './jest',
      trim($npm_bin)
    );
  }

}
