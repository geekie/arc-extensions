<?php

/**
 * Engine to use ESLint to lint Phabricator
 */
final class ESLintLinter extends ArcanistExternalLinter {

  public function getInfoName() {
    return 'ESLint';
  }

  public function getInfoURI() {
    return 'http://eslint.org';
  }

  public function getInfoDescription() {
    return 'Lint your JavaScript files';
  }

  public function getLinterName() {
    return 'ESLint';
  }

  public function getLinterConfigurationName() {
    return 'eslint';
  }

  public function getDefaultBinary() {
    list($npm_bin, $err) = execx('npm bin');
    return Filesystem::resolvePath(
      './eslint',
      trim($npm_bin)
    );
  }

  public function getInstallInstructions() {
    return pht('Add ESLint to your project with `%s`.', 'yarn add eslint');
  }

  protected function getMandatoryFlags() {
    $options = array();
    $options[] = '--format=json';
    $options[] = '--fix-dry-run';
    return $options;
  }

  protected function parseLinterOutput($path, $err, $stdout, $stderr) {
    $files = phutil_json_decode($stdout);
    $file = $files[0];

    $messages = array();

    $original = $this->getData($path);
    $output = idx($file, 'output');

    if ($output && $output != $original) {
      $messages[] = ArcanistLintMessage::newFromDictionary(array(
        'name' => 'Code format',
        'path' => $path,
        'line' => 1,
        'char' => 1,
        'code' => 'eslint-fix',
        'description' => 'This file can be autofixed',
        'severity' => ArcanistLintSeverity::SEVERITY_AUTOFIX,
        'original' => $original,
        'replacement' => $output
      ));
      $original = $output;
    }

    foreach ($file['messages'] as $eslintMsg) {
      if (!idx($eslintMsg, 'ruleId')) {
        continue;
      }
      // print_r($eslintMsg);

      $dict = array(
        'name' => $this->getLinterName(),
        'path' => $path,
        'line' => idx($eslintMsg, 'line'),
        'char' => idx($eslintMsg, 'column'),
        'code' => idx($eslintMsg, 'ruleId'),
        'description' => idx($eslintMsg, 'message'),
        'severity' => $this->getSeverity($eslintMsg),
        // 'original' => $original
      );

      $messages[] = ArcanistLintMessage::newFromDictionary($dict);
    }

    return $messages;
  }

  private function getSeverity($eslintMsg) {
    switch (idx($eslintMsg, 'severity')) {
      case '0':
      case '1':
        return ArcanistLintSeverity::SEVERITY_WARNING;
      case '2':
      default:
        return ArcanistLintSeverity::SEVERITY_ERROR;
    }
  }

}
