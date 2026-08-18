<?php

/**
 * Prints the public API surface of the generated code: every class under
 * src/, its constants, and its public methods, sorted. Committed as
 * tests/api-surface.txt, this turns a regeneration's 41-file diff into a
 * few reviewable lines and mechanically exposes BC breaks: removed lines
 * mean a major version bump, added lines a minor.
 *
 * Usage: php tools/api-surface.php > tests/api-surface.txt
 */

require __DIR__ . '/../vendor/autoload.php';

$src = realpath(__DIR__ . '/../src');
$classes = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src));
foreach ($it as $file) {
  if ($file->getExtension() !== 'php') {
    continue;
  }
  $class = strtr(substr($file->getPathname(), strlen($src) + 1, -4), '/', '\\');
  // The GPBMetadata class name derives from the proto filename, not PSR-4.
  require_once $file->getPathname();
  $classes[] = $class;
}

$lines = [];
foreach ($classes as $class) {
  if (!class_exists($class)) {
    continue;
  }
  $ref = new ReflectionClass($class);
  foreach ($ref->getConstants() as $name => $value) {
    $lines[] = "$class::$name = " . var_export($value, TRUE);
  }
  foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
    if ($method->getDeclaringClass()->getName() !== $class) {
      continue;
    }
    $params = array_map(
      fn(ReflectionParameter $p) => ($p->getType() ? $p->getType() . ' ' : '') . '$' . $p->getName(),
      $method->getParameters()
    );
    $return = $method->getReturnType() ? ': ' . $method->getReturnType() : '';
    $lines[] = "$class::{$method->getName()}(" . implode(', ', $params) . ")$return";
  }
}

sort($lines);
echo implode("\n", $lines), "\n";
