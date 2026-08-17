<?php

test('app css theme tokens match the storefront brand spec', function () {
    $spec = extractCssCustomProperties(file_get_contents(base_path('docs/references/mockup/brand-spec.md')));
    $theme = extractCssCustomProperties(file_get_contents(resource_path('css/app.css')));

    expect($spec)->not->toBeEmpty();

    foreach ($spec as $name => $value) {
        expect($theme)->toHaveKey($name)
            ->and($theme[$name])->toBe($value);
    }
});

test('storefront brand pairs used in the ui keep 4.5:1 contrast', function () {
    $theme = extractCssCustomProperties(file_get_contents(resource_path('css/app.css')));

    $pairs = [
        ['color-accent', 'color-surface'],
        ['color-brand', 'color-surface'],
        ['color-muted', 'color-bg'],
        ['color-highlight-fg', 'color-surface'],
        ['color-accent-fg', 'color-accent'],
        ['color-brand-fg', 'color-brand'],
        ['color-fg', 'color-bg'],
        ['color-danger', 'color-surface'],
    ];

    foreach ($pairs as [$foreground, $background]) {
        expect(oklchContrast($theme[$foreground], $theme[$background]))
            ->toBeGreaterThanOrEqual(4.5, "{$foreground} on {$background}");
    }
});

/**
 * @return array<string, string>
 */
function extractCssCustomProperties(string $source): array
{
    preg_match_all('/--((?:color|radius)-[a-z0-9-]+):\s*([^;]+);/', $source, $matches, PREG_SET_ORDER);

    $tokens = [];

    foreach ($matches as $match) {
        $tokens[$match[1]] = trim($match[2]);
    }

    return $tokens;
}

function oklchContrast(string $first, string $second): float
{
    $l1 = oklchRelativeLuminance($first);
    $l2 = oklchRelativeLuminance($second);
    $higher = max($l1, $l2);
    $lower = min($l1, $l2);

    return ($higher + 0.05) / ($lower + 0.05);
}

function oklchRelativeLuminance(string $value): float
{
    preg_match('/oklch\(\s*([0-9.]+)%\s+([0-9.]+)\s+([0-9.]+)\s*\)/', $value, $match);

    expect($match)->not->toBeEmpty();

    $lightness = ((float) $match[1]) / 100;
    $chroma = (float) $match[2];
    $hue = deg2rad((float) $match[3]);
    $a = $chroma * cos($hue);
    $b = $chroma * sin($hue);

    $l = ($lightness + 0.3963377774 * $a + 0.2158037573 * $b) ** 3;
    $m = ($lightness - 0.1055613458 * $a - 0.0638541728 * $b) ** 3;
    $s = ($lightness - 0.0894841775 * $a - 1.2914855480 * $b) ** 3;

    $red = 4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s;
    $green = -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s;
    $blue = -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s;

    $clamp = fn (float $channel): float => max(0.0, min(1.0, $channel));

    return 0.2126 * $clamp($red) + 0.7152 * $clamp($green) + 0.0722 * $clamp($blue);
}
