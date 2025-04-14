<?php

	abstract class CoreApplication{
		public static array $activeSections = [];

		public static function isActive(string $section): bool{
			return (isset(self::$activeSections[$section]) && self::$activeSections[$section] === true);
		}

		public static function activate(string $section): void{
			self::$activeSections[$section] = true;
		}

		public static function deactivate(string $section): void{
			self::$activeSections[$section] = false;
		}

		public static function initialise(): void{
			self::$activeSections = [
				'admin' => true,
				'fileupload' => true
			];
		}
	}
