# Arcanist extensions

This is a collection of [Arcanist](https://secure.phabricator.com/book/phabricator/article/arcanist/) extensions we use at Geekie.

- Linters
  - [ESLint](#eslint)
- Unit Tests
  - [Jest](#jest)

## Installation

The most convenient approach is to add this repository as a submodule of your project so it can be shared by everyone who works on the code, and use the same version of the linters and engines.

```shell
$ git submodule add https://github.com/geekie/arc-extensions geekie-arc-extensions
$ git submodule update --init
```

In your `.arcconfig`, enable the extension:

```json
{
  "load": ["geekie-arc-extensions"]
}
```

## Linters

### ESLint

Instead of reporting every error found like most linters do, this will already suggest the whole fixed (i.e. output of `eslint --fix`) as a single "autofix" error. Rules that can't be fixed are reported.

This is the best approach if you also use [eslint-plugin-prettier](https://github.com/prettier/eslint-plugin-prettier), because fixing each rule separately will leave the code unformatted in the end.

Example `.arclint` configuration:

```json
{
  "type": "eslint",
  "include": "(\\.jsx?)$"
}
```

## Unit Tests

### Jest

This engine will only run tests related to the changed files in the current diff, but also supports `arc unit --everything` to run all tests.

In `.arcconfig`:

```json
{
  "unit.engine": "JestTestEngine"
}
```
