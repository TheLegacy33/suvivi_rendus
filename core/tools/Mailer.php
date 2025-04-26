<?php

	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\SMTP;

	require_once 'core/tools/PHPMailer/PHPMailer.php';
	require_once 'core/tools/PHPMailer/Exception.php';
	require_once 'core/tools/PHPMailer/SMTP.php';

	class Mailer extends PHPMailer{
		private string $templateHtml, $templateText;
		private bool $defined;
		private array $variables;
		private string $senderApp;

		public function __construct($exceptions = true){
			parent::__construct($exceptions);
			$this->defined = false;
			$this->templateHtml = '';
			$this->templateText = '';
			$this->variables = [];
			$this->senderApp = '';
			try{
				$this->senderApp = DAOParametres::getByLibelle('smtp-senderapp')->getValeur();
				$this->setLanguage('fr', 'core/tools/PHPMailer/language/');
				$this->SMTPDebug = SMTP::DEBUG_OFF;                                    // Désactive le mode verbeux
				$this->CharSet = 'UTF-8';
				$this->Encoding = 'base64';
				$this->Timeout = 30;
				$this->isSMTP();                                        // Définir l'utilisation de SMTP
				$this->Host = DAOParametres::getByLibelle('smtp-host')->getValeur();
				$this->Port = DAOParametres::getByLibelle('smtp-port')->getValeur();
				$this->Helo = DAOParametres::getByLibelle('smtp-helo')->getValeur();
				if (DAOParametres::getByLibelle('smtp-auth')->getValeur() == '1'){
					$this->SMTPAuth = DAOParametres::getByLibelle('smtp-auth')->getValeur() == '1'; //true;
					$this->Username = decrypt_data(DAOParametres::getByLibelle('smtp-user')->getValeur());
					$this->Password = decrypt_data(DAOParametres::getByLibelle('smtp-pass')->getValeur());
					//					$this->SMTPSecure = 'ssl';                            // Active TLS, `ssl` également accepté
					$this->SMTPSecure = DAOParametres::getByLibelle('smtp-secure')->getValeur(); //PHPMailer::ENCRYPTION_STARTTLS;
					//$this->SMTPAutoTLS = true;
					//					$this->SMTPOptions = array("ssl" => array("verify_peer" => false, "verify_peer_name" => false, "allow_self_signed" => true));
				}
				$this->defined = true;
			}catch (Exception $exception){
				$this->defined = false;
			}
		}

		public function getTemplateHtml(): string{
			return $this->templateHtml;
		}

		public function setTemplateHtml(string $templateHtml): void{
			$this->templateHtml = file_get_contents($templateHtml);
		}

		public function getTemplateText(): string{
			return $this->templateText;
		}

		public function setTemplateText(string $templateText): void{
			$this->templateText = file_get_contents($templateText);
		}

		public function getSenderApp(): string{
			return $this->senderApp;
		}

		public function setSenderApp(string $senderApp): void{
			$this->senderApp = $senderApp;
		}

		public function setVariables(array $variables): void{
			$this->variables = $variables;
		}

		public function getVariables(): array{
			return $this->variables;
		}

		public function isDefined(): bool{
			return $this->defined;
		}

		public function compileHTML(): string{
			$template = $this->templateHtml;
			foreach ($this->variables as $key => $value){
				$template = str_replace('{{' . $key . '}}', $value ?? '', $template);
				$template = str_replace('{{ ' . $key . ' }}', $value ?? '', $template);
			}
			$this->templateHtml = $template;
			return $this->templateHtml;
		}

		public function compileText(): string{
			$template = $this->templateText;
			foreach ($this->variables as $key => $value){
				$template = str_replace('{{ ' . $key . ' }}', $value ?? '', $template);
			}
			$this->templateText = $template;
			return $this->templateText;
		}
	}
