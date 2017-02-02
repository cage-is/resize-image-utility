<?php
require_once(__DIR__ . '/../Helpers.php');

class Test
{
    /**
     * @var string
     */
    private $tempDir;

    /**
     * @var string
     */
    private $dir;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * Test constructor.
     */
    public function __construct()
    {
        $this->setHelper(new Helper);

        $this->setDir(realpath(__DIR__ . '/..'));
        $this->setTempDir($this->getDir('/tests/images/tmp'));
        $this->copyFiles();
    }

    /**
     * Run all tests.
     */
    public function run()
    {
        $this->testImageResizeWithNewFile();
        $this->testImageResizeWithSameFile();
        $this->testImageResizeFailsWhenFileAlreadySmaller();
    }

    /**
     * Verify the ability to resize an image while creating a new output file.
     */
    private function testImageResizeWithNewFile()
    {
        $inFile = $this->getTempDir('/1.jpeg');
        $outFile = $this->getTempDir('/resize_1.tiff');

        clearstatcache();
        $fileSize = filesize($inFile);

        $this->assert(
            $fileSize === 506649,
            'File size is correct'
        );

        $command = "php {$this->getScript()} --in={$inFile} --out={$outFile} --size=306649";

        $this->exec($command);

        clearstatcache();
        $this->assert(
            filesize($outFile) === 267851,
            "Output file is expected size"
        );
    }

    /**
     * Tests the ability to resize the file into the same file, overwriting the
     * file. Also verifies that there is a backup of the overwritten file made.
     */
    private function testImageResizeWithSameFile()
    {
        $this->assert(
            count(glob($this->getTempDir('/*'))) === 4,
            'The number of files in tmp directory is correct'
        );

        $inFile = $this->getTempDir('/2.jpeg');

        clearstatcache();
        $this->assert(
            filesize($inFile) === 3067348,
            'File size is correct'
        );

        $command = "php {$this->getScript()} --in={$inFile} --out-same --overwrite --size=2067348";

        $this->exec($command);

        clearstatcache();
        $this->assert(
            filesize($inFile) === 1226994,
            "Output file is expected size"
        );

        $backupFile = glob($this->getTempDir('/*.backup'));
        $this->assert(
            count($backupFile) === 1,
            'There is a file that was backed up'
        );

        $this->assert(
            filesize(current($backupFile)) === 3067348,
            "The backup file has the same file size as it did before it was moved"
        );
    }

    /**
     * Verifies that when you try to resize an image that is already at an
     * adequate size.
     */
    public function testImageResizeFailsWhenFileAlreadySmaller()
    {
        $inFile = $this->getTempDir('/3.jpeg');
        $outFile = $this->getTempDir('/fail.tiff');

        $fileSize = filesize($inFile);

        $output = $this->exec(
            "php {$this->getScript()} --in={$inFile} --out={$outFile} --size={$fileSize}"
        );

        $this->assert(
            isset($output[1]) && $output[1] === 'This file is already smaller than requested.',
            "Error message indicates file is already smaller."
        );
    }

    /**
     * All of the assertions made in this test go through this method.
     *
     * @param bool $assertion
     * @param string $description
     * @throws Exception
     */
    private function assert($assertion, $description)
    {
        $this->getHelper()->println(
            $this->getHelper()->colorText(Helper::GREEN, 'Asserting: ' . $description)
        );

        if (!$assertion) {
            throw new Exception("Failed asserting: {$description}");
        }
    }

    /**
     * @param string $dir
     * @return Test $this
     */
    private function setTempDir($dir)
    {
        if (is_dir($dir)) {
            $this->deleteTmpDir();
        }

        mkdir($dir);

        $this->tempDir = $dir;

        return $this;
    }

    /**
     * Deletes the temporary directory that's created at the beginning of
     * tests.
     */
    private function deleteTmpDir()
    {
        $dir = $this->getTempDir();
        $this->exec("rm -rfv {$dir}");
    }

    /**
     * Get the location of the temp directory.
     *
     * @param string $suffix
     * @return string
     */
    public function getTempDir($suffix = '')
    {
        return $this->tempDir . $suffix;
    }

    /**
     * Copies all of the images into the temp directory.
     *
     * @return Temp $this
     */
    private function copyFiles()
    {
        foreach (glob($this->getDir('/tests/images/*')) as $fileToCopy) {
            if (is_file($fileToCopy)) {
                copy($fileToCopy, $this->getTempDir() . '/' . basename($fileToCopy));
            }
        }

        return $this;
    }

    /**
     * Gets the directory of the utility. Also appends any suffix if one is
     * passed.
     *
     * @param string $suffix
     * @return string
     */
    public function getDir($suffix = '')
    {
        return $this->dir . $suffix;
    }

    /**
     * @param string $dir
     * @return Test $this
     */
    public function setDir($dir)
    {
        $this->dir = $dir;
        return $this;
    }

    /**
     * Get the path to the utility's script file.
     *
     * @return string
     */
    public function getScript()
    {
        return $this->getDir('/index.php');
    }

    /**
     * Runs a shell command and returns it's output.
     *
     * @param string $command
     * @param bool $silenced
     * @return array
     */
    private function exec($command, $silenced = false)
    {
        exec($command, $output);

        if (!$silenced) {
            $this->getHelper()->println(
                $this->getHelper()->colorText(Helper::BLUE, 'Executing command: ' . $command)
            );

            $this->showOutput($output);
        }

        return $output;
    }

    /**
     * @return Helper
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * @param Helper $helper
     * @return Test $this
     */
    public function setHelper($helper)
    {
        $this->helper = $helper;
        return $this;
    }

    private function showOutput(array $output)
    {
        $blueText = function($ln) {
            $this->getHelper()->println(
                $this->getHelper()->colorText(Helper::BLUE, $ln)
            );
        };

        $blueText('Output:');
        $blueText('=================');

        array_map(function($ln) use($blueText) {
            $blueText("\t{$ln}");
        }, $output);

        $blueText('=================');
    }


    /**
     * When the class is shutting down this will execute to cleanup what we've
     * changed. This will help ensure that future tests run the same.
     */
    public function __destruct()
    {
        $this->getHelper()->println("All tests completed. Cleaning up...");
        $this->deleteTmpDir();
    }
}

(new Test)->run();