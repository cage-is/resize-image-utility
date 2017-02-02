<?php
require_once(__DIR__ . '/Resize.php');
require_once(__DIR__ . '/Helpers.php');

$helper = new Helper;

$longOpts = [
    'in::' => 'The absolute path to the file that is to be reduced.',
    'out::' => 'The absolute path of where to place the reduced file.',
    'size::' => 'The size (in bytes) that the file should be reduced to.',
    'step-size::' => 'The percentage of change that the image should be downsized each iteration.',

    'out-same' => 'Use the "in" file as the place to write the file (requires --overwrite).',
    'overwrite' => 'If this option is not specified then no files will be overwritten.',
    'help' => 'Show the options for this utility.'
];
$options = getopt('', array_keys($longOpts));

/*
 * Here if there are no options specified or if the help flag is passed, then
 * we show what options and flags are available.
 */
if (isset($options['help']) || empty($options)) {
    $helper->println("Example usage:");

    $example = "\tphp index.php --in='/var/www/img.tiff' --out-same --overwrite --size=1992294 --step-size=5";

    $helper->println($helper->colorText($helper::GREEN, $example));
    $helper->println("This will take the file in '/var/www/img.tiff' and reduce it to 1992294 bytes");
    $helper->println("by reducing it's size 5% each iteration until it is smaller than 1992294 bytes.");
    $helper->println('');
    array_walk($longOpts, function($description, $option) use($helper) {
        $option = rtrim($option, ':');
        $numSpaces = 20 - strlen($option);
        $prefix = $helper->colorText($helper::GREEN, '--' . $option . str_repeat(' ', $numSpaces));
        $helper->println($prefix . $description);
    });

    die(PHP_EOL);
}

/* Load all of the options */
$inFile = $helper->arrayGet($options, 'in');
$sizeInBytes = $helper->arrayGet($options, 'size', 0);
$stepSize = $helper->arrayGet($options, 'step-size', 10);
$outFile = $helper->arrayGet($options, 'out');
$outSame = $helper->arrayGet($options, 'out-same') === false;
$overwrite = $helper->arrayGet($options, 'overwrite') === false;
$outFile = $outSame ? $inFile : $outFile;

$helper->println(
    "Downsizing {$inFile} to {$sizeInBytes} by reducing {$stepSize}% at a time, then saving to {$outFile}."
);

/* Attempt to downsize the image and catch any errors that might result. */
try {
    $resize = new Resize($inFile);
    $image = $resize->downsizeToBytes($sizeInBytes, $stepSize);

    if (file_exists($outFile)) {
        if ($overwrite) {
            rename($outFile, $outFile . uniqid('') . '.backup');
        } else {
            throw new Exception("Out file exists. Use --overwrite if you would like to overwrite the existing file.");
        }
    }

    $isDirWritable = is_writeable(dirname($outFile));
    $isFileWritable = !file_exists($outFile) || ($overwrite && is_writeable($outFile));

    if (!$isDirWritable || !$isFileWritable) {
        throw new Exception("The out file path is not writable.");
    }

    /* Save the downsized image to it's output location */
    $helper->println("Writing image to {$outFile}.");
    $image->writeImage($outFile);

    $helper->println("New file size (in bytes): {$image->getImageLength()}");
} catch(Exception $e) {
    $helper->println($e->getMessage());
}
