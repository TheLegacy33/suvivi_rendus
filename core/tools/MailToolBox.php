<?php

	abstract class MailToolBox{
		/**
		 * Génération et envoi du mail de création d'un compte à l'utilisateur concerné
		 * @param User $userBeneficiaire
		 * @return void
		 */
		public static function sendEmailCreationCompte(User $userBeneficiaire): void{
			$mailer = new Mailer();
			try{
				if ($mailer->isDefined()){
					$mailer->setFrom(DAOParametres::getByLibelle('senderemail-inscription')->getValeur(), $mailer->getSenderApp());
					if (DEV_MODE){
						$mailer->addAddress(DAOParametres::getByLibelle('destemail-contact')->getValeur(), $userBeneficiaire->getFullName());
					}else{
						$mailer->addAddress($userBeneficiaire->getEmail(), $userBeneficiaire->getFullName());
					}
					//Content
					$mailer->isHTML(); // Défini le mail au format HTML
					$mailer->Subject = "Un compte vient d'être créé pour vous sur " . $mailer->getSenderApp();
					$mailer->setVariables([
						'url_image_logo' => 'cid:art_interactivities_logo',
						'senderapp' => $mailer->getSenderApp(),
						'login_identifiant' => $userBeneficiaire->getLoginIdentifiant(),
						'generated_password' => $userBeneficiaire->getPassword(),
						'annee' => date('Y'),
						'nom_entreprise' => $mailer->getSenderApp(),
						'lien_espace_acheteur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription')
					]);
					$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR . 'logo.png', 'art_interactivities_logo');
					$lang = substr($defaultLanguage ?? 'fr', 0, 2);
					$mailer->setTemplateHtml("core/views/template/mails/mail_inscription_acheteur_$lang.phtml");
					$mailer->Body = $mailer->compileHTML();
					$mailer->setTemplateText("core/views/template/mails/mail_inscription_acheteur_$lang.txt");
					$mailer->AltBody = $mailer->compileText();
					$mailer->send();
//					debug('Mail ' . $mailer->Subject . ' envoyé à ' . $userBeneficiaire->getEmail() . '->' . $userBeneficiaire->getFullName());
				}
			}catch (Exception $e){
				$message = $mailer->ErrorInfo;
			}
		}

		/**
		 * Génération et envoi du mail contenant les informations d'une carte cadeau à la personne bénéficiaire
		 * @param User        $userBeneficiaire
		 * @param User        $userAcheteur
		 * @param CarteCadeau $carteCadeau
		 * @return void
		 */
		public static function sendEmailNewCarteCadeau(User $userBeneficiaire, User $userAcheteur, CarteCadeau $carteCadeau): void{
			$mailer = new Mailer();
			try{
				if ($mailer->isDefined()){
					//Destinataires
					$mailer->setFrom(DAOParametres::getByLibelle('senderemail-vente')->getValeur(), $mailer->getSenderApp());
					if (DEV_MODE){
						$mailer->addAddress(DAOParametres::getByLibelle('destemail-contact')->getValeur(), $userBeneficiaire->getFullName());
					}else{
						$mailer->addAddress($userBeneficiaire->getEmail(), $userBeneficiaire->getFullName());
					}
					//Content
					$mailer->isHTML(); // Défini le mail au format HTML
					$mailer->Subject = "Vous venez de recevoir une carte cadeau utilisable sur " . $mailer->getSenderApp();

					$message_carte = '<strong style="font-style: italic;">'.$carteCadeau->getTitreMessage().'</strong>';
					$message_carte .= '<p>'.nl2br($carteCadeau->getContenuMessage()).'</p>';
					$message_carte .= '<quote style="font-style: italic;">'.$carteCadeau->getFinMessage().'</quote>';

					$mailer->setVariables([
						'url_image_logo' => 'cid:art_interactivities_logo',
						'senderapp' => $mailer->getSenderApp(),
						'login_identifiant' => $userBeneficiaire->getLoginIdentifiant(),
						'annee' => date('Y'),
						'nom_entreprise' => $mailer->getSenderApp(),
						'lien_espace_acheteur' => EXTERNAL_URL . getUrl('utilisateur', 'connexion-inscription'),
						'acheteur_civilite' => '',
						'acheteur_nom' => $userAcheteur->getFullName(),
						'numero_carte' => $carteCadeau->getCodeCarte(),
						'date_activation_carte' => $carteCadeau->getDateEffet()->format('d/m/Y'),
						'montant_carte' => formatMontant($carteCadeau->getMontant()),
						'message_carte' => $message_carte
					]);
					$mailer->addEmbeddedImage(PHP_PUBLIC_IMAGES_DIR . 'logo.png', 'art_interactivities_logo');
					$lang = substr($defaultLanguage ?? 'fr', 0, 2);
					$mailer->setTemplateHtml("core/views/template/mails/mail_transmission_carte_cadeau_beneficiaire_$lang.phtml");
					$mailer->Body = $mailer->compileHTML();
					$mailer->setTemplateText("core/views/template/mails/mail_transmission_carte_cadeau_beneficiaire_$lang.txt");
					$mailer->AltBody = $mailer->compileText();
					$mailer->send();
//					debug('Mail ' . $mailer->Subject . ' envoyé à ' . $userBeneficiaire->getEmail() . '->' . $userBeneficiaire->getFullName());
				}
			}catch (Exception $e){
				$message = $mailer->ErrorInfo;
			}
		}
	}