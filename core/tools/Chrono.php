<?php
	abstract class Chrono{
		private static float $depart, $fin;

		public static function depart(): void{
			self::$depart = microtime(true);
		}

		public static function fin(): void{
			self::$fin = microtime(true);
		}

		public static function debug(): void{
			$temps = self::$fin - self::$depart;
			debug(number_format($temps, 3));
		}
	}