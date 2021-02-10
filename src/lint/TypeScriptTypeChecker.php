<?php

final class TypeScriptTypeChecker extends ArcanistExternalLinter {

  public function getInfoName() {
    return 'TypeScript type checker';
  }

  public function getInfoURI() {
    return 'https://www.typescriptlang.org';
  }

  public function getInfoDescription() {
    return 'Type check your TypeScript files';
  }

  public function getLinterName() {
    return 'TypeScript type checker';
  }

  public function getLinterConfigurationName() {
    return 'tstypechecker';
  }

  public function getDefaultBinary() {
    list($npm_bin, $err) = execx('npm bin');
    return Filesystem::resolvePath(
      './tsc',
      trim($npm_bin)
    );
  }

  private function getParserBinary() {
    list($npm_bin, $err) = execx('npm bin');
    return Filesystem::resolvePath(
      './tsc-output-parser',
      trim($npm_bin)
    );
  }

  public function getInstallInstructions() {
    return pht('Add TypeScript to your project with `%s`.', 'yarn add -D typescript');
  }

  protected function getMandatoryFlags() {
    $options = array();
    $options[] = '--noEmit';
    $options[] = '--target';
    $options[] = 'esnext';
    $options[] = '--module';
    $options[] = 'commonjs';
    $options[] = '--esModuleInterop';
    $options[] = '--allowJs';
    $options[] = '--skipLibCheck';
    $options[] = '--noImplicitAny';
    $options[] = '--strict';
    $options[] = '--lib';
    $options[] = 'ES6,DOM,ESNext,DOM.Iterable';
    $options[] = '--jsx';
    $options[] = 'react-native';
    return $options;
  }

  protected function parseLinterOutput($path, $err, $stdout, $stderr) {
    $lines = explode("\n", $stdout);
    $filteredLines = array();
    $messages = array();

    foreach ($lines as &$line) {
      if (preg_match("/^\W/", $line)) {
        $index = count($filteredLines) - 1;
        $filteredLines[$index] = $filteredLines[$index] . $line;
      } else {
        $filteredLines[] = $line;
      }
    }

    foreach($filteredLines as &$line) {
      preg_match("/^(\w.+)\((.+),(.+)\): (\w+) (\w+): (.+)$/", $line, $matches);

      if ($matches) {
        $dict = array(
          'name' => $this->getLinterName(),
          'path' => $matches[1],
          'line' => $matches[2],
          'char' => $matches[3],
          'severity' => ArcanistLintSeverity::SEVERITY_WARNING,
          'code' => $matches[5],
          'description' => $matches[6],
        );

        $message = ArcanistLintMessage::newFromDictionary($dict);
        $message->setBypassChangedLineFiltering(true);

        if($dict['path'] != $path) {
          $message->setSeverity(ArcanistLintSeverity::SEVERITY_DISABLED);
        }

        $messages[] = $message;
      }
    }

    return $messages;
  }
}
