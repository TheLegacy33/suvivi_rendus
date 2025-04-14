<?php

	class AIImage extends AIFile {
		private string $miniatureFilePath;

		public function getMiniatureFilePath(): string {
			return $this->miniatureFilePath;
		}

		public function setMiniatureFilePath(string $miniatureFilePath): void {
			$this->miniatureFilePath = $miniatureFilePath;
		}

		public function moveFile(bool $includeDateTime = true, bool $withMiniature = true): bool {
			if (parent::moveFile(includeDateTime: $includeDateTime)) {
				if ($withMiniature) {
					$this->miniatureFilePath = $this->createMiniature($this->getLocalFilePath() . 'miniatures/');
					return !empty($this->miniatureFilePath);
				}
				return true;
			}
			return false;
		}

		private function loadImage(string $fileName): Imagick {
			return new Imagick($fileName);
		}

		private function scaleImage(float $scale, Imagick $image, int $width, int $height, string $destName): bool {
			try {
				// Calcul des nouvelles dimensions
				// Calcul du ratio initial
				$ratio = $width / $height;
				// Appliquer le scaling et ajuster selon le côté le plus grand
				if ($width>$height){
					$new_width = intval($width * $scale);
					// Limiter à la largeur maximale ou minimale
					if ($new_width<MIN_WIDTH){
						$new_width = MIN_WIDTH;
					}elseif ($new_width>MAX_WIDTH){
						$new_width = MAX_WIDTH;
					}
					// Calculer la nouvelle hauteur en conservant le ratio
					$new_height = intval($new_width / $ratio);
					/*if ($new_height<MIN_HEIGHT){
						$new_height = MIN_HEIGHT;
					}else*/if ($new_height>MAX_HEIGHT){
						$new_height = MAX_HEIGHT;
						$new_width = intval($new_height * $ratio);
					}
				}else{
					$new_height = intval($height * $scale);
					// Limiter à la hauteur maximale ou minimale
					if ($new_height<MIN_HEIGHT){
						$new_height = MIN_HEIGHT;
					}elseif ($new_height>MAX_HEIGHT){
						$new_height = MAX_HEIGHT;
					}
					// Calculer la nouvelle largeur en conservant le ratio
					$new_width = intval($new_height * $ratio);
					/*if ($new_width<MIN_WIDTH){
						$new_width = MIN_WIDTH;
					}else*/if ($new_width>MAX_WIDTH){
						$new_width = MAX_WIDTH;
						$new_height = intval($new_width / $ratio);
					}
				}

				// Redimensionner l'image
				$image->resizeImage($new_width, $new_height, Imagick::FILTER_LANCZOS, 1);

				$image->setImageCompressionQuality(60);

				// Gestion de l'orientation basée sur les données EXIF
				$orientation = $image->getImageProperty('exif:Orientation') ?? 1;
				switch (intval($orientation)) {
					case 3:
						$image->rotateImage('#000', 180);
						break;
					case 6:
						$image->rotateImage('#000', 90);
						break;
					case 8:
						$image->rotateImage('#000', -90);
						break;
				}

				// Enregistrer l'image redimensionnée au format WebP
				$image->setImageFormat('webp');
				$image->writeImage($destName);
				@chmod($destName, 0666);

				return true;
			} catch (Exception $e) {
				return false;
			} finally {
				$image->clear();
			}
		}

		public function createMiniature(string $destPath): string {
			if (!str_ends_with($destPath, '/')) {
				$destPath .= '/';
			}

			if (!file_exists($destPath)) {
				mkdir($destPath, 0777, true);
			} elseif (!is_writable($destPath)) {
				@chmod($destPath, 0777);
			}

			$srcFileName = $this->getLocalFilePath() . $this->getHashedName();
			$destFileName = md5(pathinfo($this->getName(), PATHINFO_FILENAME)) . '.webp';

			list($width, $height) = getimagesize($srcFileName);

			$srcImg = $this->loadImage($srcFileName);
			$this->miniatureFilePath = $destPath . $destFileName;

			if (!$this->scaleImage(0.3, $srcImg, $width, $height, $this->miniatureFilePath)) {
				return '';
			}

			return $this->miniatureFilePath;
		}

		private static function staticLoadImage(string $fileName): Imagick {
			return new Imagick($fileName);
		}

		private static function staticScaleImage(float $scale, Imagick $image, int $width, int $height, string $destName): bool {
			try {
				// Calcul des nouvelles dimensions
				// Calcul du ratio initial
				$ratio = $width / $height;
				// Appliquer le scaling et ajuster selon le côté le plus grand
				if ($width>$height){
					$new_width = intval($width * $scale);
					// Limiter à la largeur maximale ou minimale
					if ($new_width<MIN_WIDTH){
						$new_width = MIN_WIDTH;
					}elseif ($new_width>MAX_WIDTH){
						$new_width = MAX_WIDTH;
					}
					// Calculer la nouvelle hauteur en conservant le ratio
					$new_height = intval($new_width / $ratio);
					/*if ($new_height<MIN_HEIGHT){
						$new_height = MIN_HEIGHT;
					}else*/if ($new_height>MAX_HEIGHT){
						$new_height = MAX_HEIGHT;
						$new_width = intval($new_height * $ratio);
					}
				}else{
					$new_height = intval($height * $scale);
					// Limiter à la hauteur maximale ou minimale
					if ($new_height<MIN_HEIGHT){
						$new_height = MIN_HEIGHT;
					}elseif ($new_height>MAX_HEIGHT){
						$new_height = MAX_HEIGHT;
					}
					// Calculer la nouvelle largeur en conservant le ratio
					$new_width = intval($new_height * $ratio);
					/*if ($new_width<MIN_WIDTH){
						$new_width = MIN_WIDTH;
					}else*/if ($new_width>MAX_WIDTH){
						$new_width = MAX_WIDTH;
						$new_height = intval($new_width / $ratio);
					}
				}

				// Redimensionner l'image
				$image->resizeImage($new_width, $new_height, Imagick::FILTER_LANCZOS, 1);

				$image->setImageCompressionQuality(60);

				// Gestion de l'orientation basée sur les données EXIF
				$orientation = $image->getImageProperty('exif:Orientation') ?? 1;
				switch (intval($orientation)) {
					case 3:
						$image->rotateImage('#000', 180);
						break;
					case 6:
						$image->rotateImage('#000', 90);
						break;
					case 8:
						$image->rotateImage('#000', -90);
						break;
				}

				// Enregistrer l'image redimensionnée au format WebP
				$image->setImageFormat('webp');
				$image->writeImage($destName);
				@chmod($destName, 0666);

				return true;
			} catch (Exception $e) {
				return false;
			} finally {
				$image->clear();
			}
		}

		public static function staticCreateMiniature(string $srcFilename, string $destFilename): string {
			$image = self::staticLoadImage($srcFilename);
			list($width, $height) = getimagesize($srcFilename);
			$scale = 0.3;

			@unlink($destFilename);

			try {
				// Génération de la miniature en webp
				$type = IMAGETYPE_WEBP;
				$destFilename = pathinfo($destFilename, PATHINFO_DIRNAME) . '/' . pathinfo($destFilename, PATHINFO_FILENAME) . '.webp';
				if (!self::staticScaleImage($scale, $image, $width, $height, $destFilename)){
					return 'ko';
				}else{
					return $destFilename;
				}
			} catch (Exception $e) {
				debug($e);
				return 'ko';
			}
		}
	}
