<?php

	class AppFile{
		private string|null $name, $full_path, $tmp_name, $error, $size, $type;
		private string $localFilePath;

		/**
		 * @param string      $name
		 * @param string|null $full_path
		 * @param string|null $tmp_name
		 * @param string|null $error
		 * @param string|null $size
		 * @param string|null $type
		 */
		public function __construct(string $name, string $full_path = null, string $tmp_name = null, string $error = null, string $size = null, string $type = null){
			$this->name = $name;
			$this->full_path = $full_path;
			$this->tmp_name = $tmp_name;
			$this->error = $error;
			$this->size = $size;
			$this->type = $type;
			$this->localFilePath = '';
		}

		public function getName(): string{
			return $this->name;
		}

		public function setName(string $name): void{
			$this->name = $name;
		}

		public function getFullPath(): string{
			return $this->full_path;
		}

		public function setFullPath(string $full_path): void{
			$this->full_path = $full_path;
		}

		public function getTmpName(): string{
			return $this->tmp_name;
		}

		public function setTmpName(string $tmp_name): void{
			$this->tmp_name = $tmp_name;
		}

		public function getError(): string{
			return $this->error;
		}

		public function setError(string $error): void{
			$this->error = $error;
		}

		public function getSize(): string{
			return $this->size;
		}

		public function setSize(string $size): void{
			$this->size = $size;
		}

		public function getType(): string{
			return $this->type;
		}

		public function setType(string $type): void{
			$this->type = $type;
		}

		public function getLocalFilePath(): string{
			return $this->localFilePath;
		}

		public function getHashedName(): string{
			$fileName = pathinfo($this->name, PATHINFO_FILENAME);
			$extension = pathinfo($this->name, PATHINFO_EXTENSION);
			return md5($fileName).'.'.$extension;
		}

		public function setLocalFilePath(string $localFilePath): void{
			if (!str_ends_with($localFilePath, '/')){
				$localFilePath.= '/';
			}
			$this->localFilePath = $localFilePath;

			if (!(file_exists($this->localFilePath))){
				mkdir($this->localFilePath, recursive: true);
				@chmod($this->localFilePath, 0777);
			}elseif (!is_writeable($this->localFilePath)){
				@chmod($this->localFilePath, 0777);
			}
		}

		public static function setStoragePath(string $storagePath): string{
			if (!str_ends_with($storagePath, '/')){
				$storagePath.= '/';
			}
			if (!(file_exists($storagePath))){
				mkdir($storagePath, recursive: true);
				@chmod($storagePath, 0777);
			}elseif (!is_writeable($storagePath)){
				@chmod($storagePath, 0777);
			}
			return $storagePath;
		}
		
		public function moveFile(bool $includeDateTime = true): bool{
			if ($includeDateTime){
				$fileName = pathinfo($this->name, PATHINFO_FILENAME).date_create('now')->format('Hisv');
			}else{
				$fileName = pathinfo($this->name, PATHINFO_FILENAME);
			}
			$extension = pathinfo($this->name, PATHINFO_EXTENSION);
			$this->name = $fileName.'.'.$extension;
			$this->full_path = $this->localFilePath.$this->getName();

			if (move_uploaded_file($this->tmp_name, $this->full_path)){
				@chmod($this->full_path, 0666);
				return true;
			}else{
				return false;
			} 
		}
	}