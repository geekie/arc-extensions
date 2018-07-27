'use strict';

const getSummary = require('jest-cli/build/reporters/utils').getSummary;
const CLEAR = '\r\x1B[K\r\x1B[1A';

function getHeight(string) {
  let height = 0;
  for (let i = 0; i < string.length; i++) {
    if (string[i] === '\n') {
      height++;
    }
  }
  return height;
}

module.exports = function() {
  let clear = '';
  let estimatedTime;
  let _aggregatedResults;
  let interval;

  this.onRunStart = function(aggregatedResults, options) {
    _aggregatedResults = aggregatedResults;
    const total = aggregatedResults.numTotalTestSuites;
    estimatedTime = (options && options.estimatedTime) || 0;
    interval = setInterval(render, 1000);
    process.stderr.write('Running ' + total + ' tests...\n\n');
  };

  this.onTestResult = function(test, testResult, aggregatedResults) {
    _aggregatedResults = aggregatedResults;
  };

  this.onRunComplete = function() {
    clearInterval(interval);
    render();
    process.stderr.write('\n\n');
  };

  function render() {
    const output = getSummary(_aggregatedResults, { estimatedTime: estimatedTime });
    process.stderr.write(clear + output);
    clear = CLEAR.repeat(getHeight(output));
  }
};
