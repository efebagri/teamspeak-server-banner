<?php

declare(strict_types=1);

final class ImageDrawer {
    private const INTERPOLATION = IMG_BICUBIC;

    public function __construct(
        private readonly GdImage $banner,
        private readonly float $width,
        private readonly float $height
    ) {}

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

    private function calculateTextSize(float $size): float {
        return ($this->width * $size) + ($this->height * $size);
    }
}