<?php

declare(strict_types=1);

final class BannerDrawing {
    public function __construct(
        private GdImage $banner,
        private string $font
    ) {}

    public function drawText(
        float $textSize,
        array $color,
        float $width,
        float $height,
        string $text,
        float $x,
        float $y
    ): void {
        $size = $width * $textSize + $height * $textSize;
        $box = imagettfbbox($size, 0, $this->font, $text);

        if ($box === false) {
            throw new RuntimeException('Failed to calculate text boundaries');
        }

        $textWidth = abs($box[2]) - abs($box[0]);
        $textHeight = abs($box[5]) - abs($box[3]);

        imagettftext(
            $this->banner,
            $size,
            0,
            (int)($width * 100 / $x - $textWidth / 2),
            (int)($height * 100 / $y + $textHeight / 2),
            $this->createColor($color),
            $this->font,
            $text
        );
    }

    public function drawLine(
        float $textSize,
        array $color,
        float $width,
        float $height,
        string $text,
        float $x,
        float $lineThickness
    ): void {
        $size = $width * $textSize + $height * $textSize;
        $box = imagettfbbox($size, 0, $this->font, $text);

        if ($box === false) {
            throw new RuntimeException('Failed to calculate text boundaries');
        }

        $textWidth = abs($box[2]) - abs($box[0]);

        imagefilledrectangle(
            $this->banner,
            (int)($width * 100 / $x - $textWidth / 2 + 10),
            (int)($height * (50 - $lineThickness)),
            (int)(($width * 100 / $x - $textWidth / 2) + $textWidth),
            (int)($height * (50 + $lineThickness)),
            $this->createColor($color)
        );
    }

    public function drawImage(
        int $width,
        int $height,
        string $imagePath,
        float $imageSize
    ): void {
        $imageContent = file_get_contents($imagePath);
        if ($imageContent === false) {
            throw new RuntimeException("Failed to load image: $imagePath");
        }

        $img = imagecreatefromstring($imageContent);
        if ($img === false) {
            throw new RuntimeException("Failed to create image from string");
        }

        [$imgWidth, $imgHeight] = getimagesize($imagePath);
        if ($imgWidth === false || $imgHeight === false) {
            throw new RuntimeException("Failed to get image dimensions");
        }

        $resWidth = (int)($imgWidth / 100 * $imageSize);
        $resHeight = (int)($imgHeight / 100 * $imageSize);

        $resized = imagescale($img, $resWidth, $resHeight, IMG_BICUBIC);
        if ($resized === false) {
            throw new RuntimeException("Failed to resize image");
        }

        imagecopy(
            $this->banner,
            $resized,
            (int)($width / 2 - $resWidth / 2),
            (int)($height / 2 - $resHeight / 2),
            0,
            0,
            $resWidth,
            $resHeight
        );

        imagedestroy($img);
        imagedestroy($resized);
    }

    private function createColor(array $rgba): int {
        return imagecolorallocatealpha(
            $this->banner,
            $rgba[0],
            $rgba[1],
            $rgba[2],
            $rgba[3] ?? 0
        );
    }
}