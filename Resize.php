<?php
class Resize
{
    /**
     * @var string
     */
    private $inputFile;

    /**
     * @var Imagick
     */
    private $image;

    /**
     * @var string
     */
    private $tempFile;

    /**
     * Resize constructor.
     * @param string $inputFile
     * @throws Exception
     */
    public function __construct($inputFile)
    {
        $this->setInputFile($inputFile)
            ->createImage()
            ->setTempFile(tempnam(sys_get_temp_dir(), 'resize_') . '.tiff');

        $isReadable = is_readable($this->getTempFile());
        $isWritable = is_writeable($this->getTempFile());

        if (!$isReadable || !$isWritable) {
            throw new Exception("This process is not able to read and write in the temp directory.");
        }
    }

    /**
     * @return Resize $this
     * @throws Exception
     */
    private function createImage()
    {
        $inputFile = $this->getInputFile();
        if (!file_exists($inputFile) || !is_readable($inputFile)) {
            throw new Exception("Cannot open the image at: {$inputFile}");
        }

        $this->setImage(
            new Imagick($inputFile)
        );

        return $this;
    }

    /**
     * Reduce the size of the image by a percentage until it is the expected
     * size or smaller. By default the reduction amount for each iteration is
     * 10%.
     *
     * @param int $expectedSizeInBytes
     * @param int $stepSizeInPercent
     * @return Imagick
     * @throws Exception
     */
    public function downsizeToBytes($expectedSizeInBytes, $stepSizeInPercent = 10)
    {
        if ($expectedSizeInBytes < 100) {
            throw new Exception("The requested file size is too small. Please choose something larger than 100 bytes.");
        }

        $image = $this->getImage();
        $currentSizeInBytes = $image->getImageLength();

        /* If it's too small already, throw exception. */
        if ($currentSizeInBytes <= $expectedSizeInBytes) {
            throw new Exception("This file is already smaller than requested.");
        }

        $height = $image->getImageHeight();
        $width = $image->getImageWidth();

        /*
         * We calculate the step amount here because this causes the number of
         * possible iterations to be fixed. e.g. If we are doing 10% each time
         * then the maximum number of iterations is 10. If we calculated the
         * step inside of the while, this would not be the case.
         */
        $stepDecimal = $stepSizeInPercent / 100;

        $heightSubtract = $height * $stepDecimal;
        $widthSubtract = $width * $stepDecimal;

        /*
         * While the current size of the image is too large, keep reducing it's
         * size until it's small enough.
         */
        while ($expectedSizeInBytes < $currentSizeInBytes) {
            $height = $image->getImageHeight();
            $width = $image->getImageWidth();


            $newHeight = $height - $heightSubtract;
            $newWidth = $width - $widthSubtract;
            
            $image->sampleImage($newWidth, $newHeight);
            $image->writeImage($this->getTempFile());

            $image->readImage($this->getTempFile());

            $currentSizeInBytes = $image->getImageLength();
        }

        return $image;
    }

    /**
     * @return string
     */
    public function getInputFile()
    {
        return $this->inputFile;
    }

    /**
     * @param $inputFile
     * @return Resize $this
     */
    public function setInputFile($inputFile)
    {
        $this->inputFile = $inputFile;
        return $this;
    }

    /**
     * @return Imagick
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param Imagick $image
     * @return Resize $this
     */
    public function setImage(Imagick $image)
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @return string
     */
    public function getTempFile()
    {
        return $this->tempFile;
    }

    /**
     * @param mixed $tempFile
     * @return Resize $this
     */
    public function setTempFile($tempFile)
    {
        if (!file_exists($tempFile)) {
            touch($tempFile);
        }

        $this->tempFile = $tempFile;
        return $this;
    }
}
