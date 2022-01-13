<?php

/**
 * Returns a command to run a Node package, with support for npm and yarn (v1).
 */
function getPkgManagerRunner($projectRoot, $bin) {
  $path = Filesystem::resolvePath($projectRoot, "yarn.lock");
  if (Filesystem::pathExists($path)) {
    list($stdout, $err) = execx("yarn -s --cwd %s bin %s", $projectRoot, $bin);
    if ($stdout) {
      return trim($stdout);
    }
  }

  list($stdout, $err) = execx("npm bin");
  if ($stdout) {
    $path = trim($stdout);
  }

  if (isset($path) && $path) {
    $resolvedBin = Filesystem::resolvePath("eslint", $path);
    if (Filesystem::binaryExists($resolvedBin)) {
      return $resolvedBin;
    }
  }

  return $bin;
}

function getPkgManagerInstall($projectRoot, $pkg) {
  $path = Filesystem::resolvePath($projectRoot, "yarn.lock");
  if (Filesystem::pathExists($path)) {
    return csprintf("yarn add --dev %s", $pkg);
  }

  return csprintf("npm --save-dev %s", $pkg);
}
