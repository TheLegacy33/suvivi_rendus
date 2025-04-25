<?php

	abstract class MailToolBox{
		/**
		 * Génération et envoi du mail contenant les informations d'une carte cadeau à la personne bénéficiaire
		 * @param Etudiant $etudiant
		 * @return void
		 */
		public static function sendEmailCodeConnexion(Etudiant $etudiant): void{
			$mailer = new Mailer();
			try{
				if ($mailer->isDefined()){
					//Destinataires
					$mailer->setFrom(DAOParametres::getByLibelle('senderemail-default')->getValeur(), $mailer->getSenderApp());
					if (DEV_MODE){
						$mailer->addAddress(DAOParametres::getByLibelle('destemail-support')->getValeur(), $etudiant->getFullName());
					}else{
						$mailer->addAddress($etudiant->getEmail(), $etudiant->getFullName());
					}
					//Content
					$mailer->isHTML(); // Défini le mail au format HTML
					$mailer->Subject = "Votre code de connexion sur " . $mailer->getSenderApp();

					$mailer->setVariables([
						'url_image_logo' => 'cid:studentapp_logo',
						'nom_application' => APP_NAME,
						'url_application' => EXTERNAL_URL,
						'senderapp' => $mailer->getSenderApp(),
						'generated_code' => $etudiant->getCodeConnexion(),
						'date_validite_code' => $etudiant->getDateExpirationCodeConnexion()->format('d/m/Y H:i:s'),
						'annee' => date('Y'),
						'nom_entreprise' => $mailer->getSenderApp(),
					]);
					$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR.'logo.png', 'studentapp_logo');
					$mailer->setTemplateHtml("core/views/template/mails/mail_transmission_code_connexion.phtml");
					$mailer->Body = $mailer->compileHTML();
					$mailer->setTemplateText("core/views/template/mails/mail_transmission_code_connexion.txt");
					$mailer->AltBody = $mailer->compileText();
					$mailer->send();
				}
			}catch (Exception $e){
				$message = $mailer->ErrorInfo;
			}
		}

		/**
		 * Génération et envoi du mail contenant les informations de confirmation de l'envoi d'un fichier
		 * @param Etudiant $etudiant
		 * @param Fichier  $fichier
		 * @return void
		 */
		public static function sendEmailConfirmationFichier(Etudiant $etudiant, Fichier $fichier): void{
			$mailer = new Mailer();
			try{
				if ($mailer->isDefined()){
					//Destinataires
					$mailer->setFrom(DAOParametres::getByLibelle('senderemail-default')->getValeur(), $mailer->getSenderApp());
					if (DEV_MODE){
						$mailer->addAddress(DAOParametres::getByLibelle('destemail-support')->getValeur(), $etudiant->getFullName());
					}else{
						$mailer->addAddress($etudiant->getEmail(), $etudiant->getFullName());
					}
					//Content
					$mailer->isHTML(); // Défini le mail au format HTML
					$mailer->Subject = "Confirmation de votre envoi sur " . $mailer->getSenderApp();

					$mailer->setVariables([
						'url_image_logo' => 'cid:studentapp_logo',
						'nom_application' => APP_NAME,
						'url_application' => EXTERNAL_URL,
						'senderapp' => $mailer->getSenderApp(),
						'date_heure_envoi' => $fichier->getDateEnvoi()->format('d/m/Y H:i:s'),
						'annee' => date('Y'),
						'nom_entreprise' => $mailer->getSenderApp(),
					]);
					$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR.'logo.png', 'studentapp_logo');
					$mailer->setTemplateHtml("core/views/template/mails/mail_confirm_envoi_fichier.phtml");
					$mailer->Body = $mailer->compileHTML();
					$mailer->setTemplateText("core/views/template/mails/mail_confirm_envoi_fichier.txt");
					$mailer->AltBody = $mailer->compileText();
					$mailer->addAttachment($fichier->getChemin());
					$mailer->send();
				}
			}catch (Exception $e){
				$message = $mailer->ErrorInfo;
			}
		}
	}