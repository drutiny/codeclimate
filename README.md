# Drutiny formatter for Code Climate Engine Specification

The [Code Climate Engine Specification](https://github.com/codeclimate/platform/blob/master/spec/analyzers/SPEC.md)
is a format for displaying issues in code quality reporting tools such as the
[Code Quality](https://docs.gitlab.com/ee/user/project/merge_requests/code_quality.html) widget on GitLab.

## Installation

```
composer install
```

## Usage

```
./vendor/bin/drutiny profile:run test none:test -l test.com --format=codeclimate
```
Recommend using PHP 7.4.
