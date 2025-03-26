<?php

declare(strict_types=1);

final class ImageDrawer {
    private const INTERPOLATION = IMG_BICUBIC;

    /**
     * @param GdImage $banner
     * @param float $width
     * @param float $height
     */
    public function __construct(
        private readonly GdImage $banner,
        private readonly float $width,
        private readonly float $height
    ) {}

    /**
     * @param float $size
     * @param int $color
     * @param string $font
     * @param string $text
     * @param float $x
     * @param float $y
     * @return void
     */
    public function drawText(
        float $size,
        int $color,
        string $font,
        string $text,
        float $x,
        float $y
    ): void {
        $textSize = $this->calculateTextSize($size);
        $box = imagettfbbox($textSize, 0, $font, $text);

        if ($box === false) {
            throw new RuntimeException('Failed to calculate text bounds');
        }

        $textWidth = abs($box[2]) - abs($box[0]);
        $textHeight = abs($box[5]) - abs($box[3]);

        $posX = ($this->width * 100 / $x) - ($textWidth / 2);
        $posY = ($this->height * 100 / $y) + ($textHeight / 2);

        imagettftext(
            $this->banner,
            $textSize,
            0,
            (int)$posX,
            (int)$posY,
            $color,
            $font,
            $text
        );
    }

    /**
     * Draws a line with specified properties, including size, color, font, and thickness.
     *
     * @param float $size The size of the text to be drawn.
     * @param int $color The color of the line.
     * @param string $font The path to the font file.
     * @param string $text The text to be drawn.
     * @param float $x The x-coordinate as a percentage of width.
     * @param float $thickness The thickness of the line.
     *
     * @return void
     */
    public function drawLine(
        float $size,
        int $color,
        string $font,
        string $text,
        float $x,
        float $thickness
    ): void {
        $textSize = $this->calculateTextSize($size);
        $box = imagettfbbox($textSize, 0, $font, $text);

        if ($box === false) {
            throw new RuntimeException('Failed to calculate text bounds');
        }

        $textWidth = abs($box[2]) - abs($box[0]);
        $startX = ($this->width * 100 / $x) - ($textWidth / 2) + 10;

        imagefilledrectangle(
            $this->banner,
            (int)$startX,
            (int)($this->height * (50 - $thickness)),
            (int)($startX + $textWidth),
            (int)($this->height * (50 + $thickness)),
            $color
        );
    }

    /**
     * Draws and resizes an image onto the banner at a specified size, centering it.
     *
     * @param string $imagePath The file path of the image to be drawn.
     * @param float $size The percentage size to which the image should be resized.
     * @return void
     * @throws RuntimeException If the image cannot be read, is invalid, or resizing fails.
     */
    public function drawImage(string $imagePath, float $size): void {
        $imageData = file_get_contents($imagePath);
        if ($imageData === false) {
            throw new RuntimeException("Could not read image: $imagePath");
        }

        $image = imagecreatefromstring($imageData);
        if ($image === false) {
            throw new RuntimeException("Invalid image data: $imagePath");
        }

        [$imgWidth, $imgHeight] = getimagesize($imagePath);
        $newWidth = $imgWidth / 100 * $size;
        $newHeight = $imgHeight / 100 * $size;

        $resizedImage = imagescale($image, (int)$newWidth, (int)$newHeight, self::INTERPOLATION);
        if ($resizedImage === false) {
            throw new RuntimeException('Failed to resize image');
        }

        $destX = (int)($this->width / 2 - $newWidth / 2);
        $destY = (int)($this->height / 2 - $newHeight / 2);

        imagecopy(
            $this->banner,
            $resizedImage,
            $destX,
            $destY,
            0,
            0,
            (int)$newWidth,
            (int)$newHeight
        );
    }

    /**
     * Calculates the text size based on provided size and the dimensions.
     *
     * @param float $size The base size to calculate the text size.
     * @return float The calculated text size.
     */
    private function calculateTextSize(float $size): float {
        return ($this->width * $size) + ($this->height * $size);
    }
}