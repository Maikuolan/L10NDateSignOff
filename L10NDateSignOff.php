<?php
/**
 * Quick sign-off tool for when modifying localised documentation and similar (last modified: 2023.07.24).
 *
 * @link https://github.com/Maikuolan/L10NDateSignOff
 *
 * License: MIT License
 * @see LICENSE.txt
 *
 * @author Caleb M (Maikuolan)
 */

// Path to vendor directory.
$Vendor = __DIR__ . DIRECTORY_SEPARATOR . 'vendor';

// Composer's autoloader.
require $Vendor . DIRECTORY_SEPARATOR . 'autoload.php';

$ParseVars = function (array $Needles, string $Haystack): string {
    if (empty($Haystack)) {
        return '';
    }
    foreach ($Needles as $Key => $Value) {
        if (!is_array($Value)) {
            $Haystack = str_replace('{' . $Key . '}', $Value, $Haystack);
        }
    }
    return $Haystack;
};

$Time = time();

$Populate = [
    'YYYY' => $_POST['year'] ?? date('Y', $Time),
    'MM' => $_POST['month'] ?? date('n', $Time),
    'DD' => $_POST['day'] ?? date('j', $Time),
    'Out' => ''
];

$L10NSource = __DIR__ . DIRECTORY_SEPARATOR . 'l10n';
$SupportedObject = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($L10NSource), \RecursiveIteratorIterator::SELF_FIRST);
$Populate['Out'] = '';
$Numerals = new \Maikuolan\Common\NumberFormatter('NoSep-1');
$Months = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December'
];

$L10NObj = new \Maikuolan\Common\L10N();

foreach ($SupportedObject as $Item => $List) {
    if (!$Item || !is_readable($Item) || substr($Item, -4) !== '.yml') {
        continue;
    }
    $L10N = new \Maikuolan\Common\YAML(file_get_contents($Item));
    if (!($L10N instanceof \Maikuolan\Common\YAML)) {
        continue;
    }
    $L10N = $L10N->Data;
    if (!isset($L10N['Preferred Format'], $L10N['Local Name'], $L10N['Numerals'])) {
        continue;
    }
    $Numerals->ConversionSet = $L10N['Numerals'];
    $Populate['Out'] .= '<tr><td align="right">' . $L10N['Local Name'] . '</td><td align="left">';
    $Values = [
        'YYYY' => $Numerals->format($Populate['YYYY']),
        'M' => $Numerals->format($Populate['MM']),
        'D' => $Numerals->format($Populate['DD'])
    ];
    $Values['MM'] = $Populate['MM'] < 10 ? $Numerals->format(0) . $Values['M'] : $Values['M'];
    $Values['DD'] = $Populate['DD'] < 10 ? $Numerals->format(0) . $Values['D'] : $Values['D'];
    $Values['Month'] = isset($Months[$Populate['MM']], $L10N[$Months[$Populate['MM']]]) ? $L10N[$Months[$Populate['MM']]] : '';
    $Dir = $L10NObj->getDirectionality(preg_replace('~^(?:.*)[/\\\](.*?)\.yml$~', '\1', $Item));
    $Populate['Out'] .= $ParseVars(
        $Values,
        '<input type="button" onclick="javascript:if(navigator.clipboard){var doCopy=\'' . $L10N['Preferred Format'] . '\';navigator.clipboard.writeText(doCopy);alert(\'Copy success\')}else{alert(\'Copy failure\')};" value="' . $L10N['Preferred Format'] . '" dir="' . $Dir . '" />'
    );
    $Populate['Out'] .= '</td></tr>';
}

echo $ParseVars($Populate, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'template.html'));
