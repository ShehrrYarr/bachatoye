<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Guard for sub-path hosting (23.230.253.206/alzaitoontraders): a URL written
 * by hand in a view must start with @base, or it hits the server root and the
 * feature silently breaks there while still working on flashsale.fashion.
 *
 *   fetch('/admin/x')            ✗   fetch('@base/admin/x')        ✓
 *   fetch(`/{{ $p }}/x`)         ✗   fetch(`@base/{{ $p }}/x`)     ✓
 *   url('/x'), route(...), asset(...), and suffixes like `… + '/edit'` are fine.
 */
class ViewUrlsUseBaseTest extends TestCase
{
    // Quote/backtick + "/" + letter, Blade echo or JS template var — unless it
    // is an argument to url()/asset() or a path suffix after "+".
    private const ROOT_RELATIVE = '/(?<!url\()(?<!asset\()(?<!\+ )(?<!\+)[`\'"]\/[A-Za-z{$]/';

    public function test_hand_written_urls_in_views_start_with_base(): void
    {
        $viewsDir   = dirname(__DIR__, 2) . '/resources/views';
        $offenders  = [];

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
        foreach ($files as $file) {
            if (!str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            foreach (file($file->getPathname()) as $i => $line) {
                if (preg_match(self::ROOT_RELATIVE, $line)) {
                    $relative    = str_replace('\\', '/', substr($file->getPathname(), strlen($viewsDir) + 1));
                    $offenders[] = "{$relative}:" . ($i + 1) . '  ' . trim($line);
                }
            }
        }

        $this->assertSame([], $offenders,
            "Root-relative URLs in views break the app under a sub-path. Prefix them with @base:\n"
            . implode("\n", $offenders));
    }
}
