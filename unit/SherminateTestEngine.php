<?php

/**
 * Forked from the 'py.test' unit test engine
 */
final class SherminateTestEngine extends BaseGeekieTestEngine {

  private $projectRoot;

  final public function shouldEchoTestResults() {
    return false;
  }

  public function run() {
    $working_copy = $this->getWorkingCopy();
    $project_tests = $working_copy->getProjectConfig('unit.SherminateTestEngine.project');
    $legacy_sherminate = $working_copy->getProjectConfig('unit.SherminateTestEngine.use_legacy_sherminate');

    $this->projectRoot = $working_copy->getProjectRoot();

    $xunit_tmp = new TempFile();
    $cover_tmp = new TempFile();

    $future = $this->buildTestFuture($project_tests, $xunit_tmp, $cover_tmp, $legacy_sherminate);
    list($err, $stdout, $stderr) = $this->resolveFuture($future);

    if (!Filesystem::pathExists($xunit_tmp)) {
      throw new CommandException(
        pht('Command failed with error #%s!', $err),
        $future->getCommand(),
        $err,
        $stdout,
        $stderr);
    }

    if ($this->getEnableCoverage() !== false) {
        $future = new ExecFuture('coverage xml -o %s', $cover_tmp);
        $future->setCWD($this->projectRoot);
        $future->resolvex();
    }

    return $this->parseTestResults($xunit_tmp, $cover_tmp);
  }

  public function buildTestFuture($project_tests, $xunit_tmp, $cover_tmp, $legacy_sherminate) {
    $paths = $this->getPaths();

    if ($legacy_sherminate) {
      $cmd_line = csprintf('glibs.sherman.nose --processes=0 --all-tests --with-xunit --xunit-file=%s', $xunit_tmp);
    } else {
      $cmd_line = csprintf('nose --with-xunit --xunit-file=%s %s', $xunit_tmp, $project_tests);
    }

    if ($this->getEnableCoverage() !== false) {
      $cmd_line = csprintf(
        'coverage run --source %s -m %C',
        $project_tests,
        $cmd_line);
    }

    return new ExecFuture('%C', $cmd_line);
  }

  public function parseTestResults($xunit_tmp, $cover_tmp) {
    $parser = new ArcanistXUnitTestResultParser();
    $results = $parser->parseTestResults(
      Filesystem::readFile($xunit_tmp));

    if ($this->getEnableCoverage() !== false) {
      $coverage_report = $this->readCoverage($cover_tmp);
      foreach ($results as $result) {
          $result->setCoverage($coverage_report);
      }
    }

    return $results;
  }

  public function readCoverage($path) {
    $coverage_data = Filesystem::readFile($path);
    if (empty($coverage_data)) {
       return array();
    }

    $coverage_dom = new DOMDocument();
    $coverage_dom->loadXML($coverage_data);

    $paths = $this->getPaths();
    $reports = array();
    $classes = $coverage_dom->getElementsByTagName('class');

    foreach ($classes as $class) {
      // filename is actually python module path with ".py" at the end,
      // e.g.: tornado.web.py
      $relative_path = explode('.', $class->getAttribute('filename'));
      array_pop($relative_path);
      $relative_path = implode('/', $relative_path);

      // first we check if the path is a directory (a Python package), if it is
      // set relative and absolute paths to have __init__.py at the end.
      $absolute_path = Filesystem::resolvePath($relative_path);
      if (is_dir($absolute_path)) {
        $relative_path .= '/__init__.py';
        $absolute_path .= '/__init__.py';
      }

      // then we check if the path with ".py" at the end is file (a Python
      // submodule), if it is - set relative and absolute paths to have
      // ".py" at the end.
      if (is_file($absolute_path.'.py')) {
        $relative_path .= '.py';
        $absolute_path .= '.py';
      }

      if (!file_exists($absolute_path)) {
        continue;
      }

      if (!in_array($relative_path, $paths)) {
        continue;
      }

      // get total line count in file
      $line_count = count(file($absolute_path));

      $coverage = '';
      $start_line = 1;
      $lines = $class->getElementsByTagName('line');
      for ($ii = 0; $ii < $lines->length; $ii++) {
        $line = $lines->item($ii);

        $next_line = intval($line->getAttribute('number'));
        for ($start_line; $start_line < $next_line; $start_line++) {
            $coverage .= 'N';
        }

        if (intval($line->getAttribute('hits')) == 0) {
            $coverage .= 'U';
        } else if (intval($line->getAttribute('hits')) > 0) {
            $coverage .= 'C';
        }

        $start_line++;
      }

      if ($start_line < $line_count) {
        foreach (range($start_line, $line_count) as $line_num) {
          $coverage .= 'N';
        }
      }

      $reports[$relative_path] = $coverage;
    }

    return $reports;
  }

}
