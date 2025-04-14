<?php
	/**
	 * @var string  $section
	 * @var string  $action
	 * @var Devises $defaultCurrency
	 *
	 * Controller pour les appels API Stripe
	 *
	 * /**
	 *  Infos Stripe
	 *  Votre numéro de compte client : F927C4
	 * https://stripe.com/docs
	 * https://docs.stripe.com
	 * https://docs.stripe.com/api/
	 */

	use Stripe\Exception\ApiErrorException;

	require_once 'core/tools/StripeToolkit.php';
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	$idUserLogged = Session::getActiveSession()->getUserId();
	switch ($action){
		case 'getpubkey':
			{
				http_response_code(200);
				print(json_encode(['key' => StripeToolkit::getActivePublicKey(), 'return' => 'success']));
				break;
			}
		case 'createPaymentIntent':
			{
				$idUser = $_POST['idAcheteur'] ?? 0;
				$emailAcheteur = $_POST['emailAcheteur'] ?? '';
				if ($idUserLogged != $idUser){
					http_response_code(500);
					print(json_encode(['intention' => false, 'return' => 'error']));
				}else{
					//Lien avec le client
					$user = DAOUser::getById($idUser);
					//Panier existe ?
					$panier = DAOPanier::getActiveCartByUserId($user->getId());
					if (!is_null($panier)){
						/**
						 * @var Adresse $adresseFacturation
						 */
						$adresseFacturation = $panier->getAdresseFacturation();
						if (!is_null($user->getStripeCustomerId())){
							try{
								$customer = StripeToolkit::getStripeClient()->customers->retrieve($user->getStripeCustomerId(), []);
								$customer = StripeToolkit::getStripeClient()->customers->update($customer->id, ['phone' => $user->getTelPortable(), 'email' => $user->getEmail() != $emailAcheteur ? $emailAcheteur : $user->getEmail(), 'address' => ['city' => $adresseFacturation->getCpVille()->getVille(), 'country' => $adresseFacturation->getPays()->getCodeIso2(), 'line1' => $adresseFacturation->getAdresse1() ?? '', 'line2' => $adresseFacturation->getAdresse2() ?? '', 'postal_code' => $adresseFacturation->getCpVille()->getCodepostal()], 'metadata' => ['internal_id' => $user->getId()]]);
							}catch (ApiErrorException $e){
								$customer = StripeToolkit::getStripeClient()->customers->create(['email' => $user->getEmail() != $emailAcheteur ? $emailAcheteur : $user->getEmail(), 'name' => $user->getFullName(), 'phone' => $user->getTelPortable(), 'address' => ['city' => $adresseFacturation->getCpVille()->getVille(), 'country' => $adresseFacturation->getPays()->getCodeIso2(), 'line1' => $adresseFacturation->getAdresse1() ?? '', 'line2' => $adresseFacturation->getAdresse2() ?? '', 'postal_code' => $adresseFacturation->getCpVille()->getCodepostal()], 'metadata' => ['internal_id' => $user->getId()]]);
							}
						}else{
							$customer = StripeToolkit::getStripeClient()->customers->create(['email' => $user->getEmail() != $emailAcheteur ? $emailAcheteur : $user->getEmail(), 'name' => $user->getFullName(), 'phone' => $user->getTelPortable(), 'address' => ['city' => $adresseFacturation->getCpVille()->getVille(), 'country' => $adresseFacturation->getPays()->getCodeIso2(), 'line1' => $adresseFacturation->getAdresse1() ?? '', 'line2' => $adresseFacturation->getAdresse2() ?? '', 'postal_code' => $adresseFacturation->getCpVille()->getCodepostal()], 'metadata' => ['internal_id' => $user->getId()]]);
							$user->setStripeCustomerId($customer->id);
							DAOUser::updateStripeCustomerId($user);
						}
						$panier->setLignes(DAOPanier::getLignesByIdPanier($panier->getId()));
						$panier->setCartesCadeaux(DAOCarteCadeau::getByIdPanier($panier->getId()));
						$montant = round($panier->calculMontantAPayer() * 100);
						if (!is_null($customer) && $customer->id !== ''){
							// Ajout d'une intention de paiement
							$intention = StripeToolkit::getStripeClient()->paymentIntents->create(['amount' => $montant, 'currency' => $defaultCurrency->getDevisesAbrege(), //'eur',
								'automatic_payment_methods' => ['enabled' => true], 'capture_method' => 'manual', 'receipt_email' => $emailAcheteur, 'description' => 'Achat AI du ' . date('d/m/Y') . ' à ' . date('H:i:s'), 'customer' => $customer->id, //							'expand' => ['latest_charge'],
								//							'payment_method_options' => ['card' => ['request_multicapture' => 'if_available']]
							]);
							http_response_code(200);
							print(json_encode(['intention' => $intention, 'return' => 'success']));
						}else{
							http_response_code(500);
							print(json_encode(['intention' => false, 'return' => 'error']));
						}
					}else{
						http_response_code(500);
						print(json_encode(['intention' => false, 'return' => 'error']));
					}
				}
				break;
			}
		case 'capturePayementIntent':
			{
				break;
			}
		case 'confirmPaymentIntent':
			{
				break;
			}
		case 'cancelPaymentIntent':
			{
				$idPaymentIntent = $_POST['idPaymentIntent'] ?? null;
				$clientSecret = $_POST['clientSecret'] ?? null;
				if (!is_null($idPaymentIntent) && !is_null($clientSecret)){
					$paymentIntent = StripeToolkit::getStripeClient()->paymentIntents->retrieve($idPaymentIntent);
					if ($paymentIntent->id === $idPaymentIntent){
						StripeToolkit::getStripeClient()->paymentIntents->cancel($paymentIntent->id, ["cancellation_reason" => 'abandoned']);
						http_response_code(200);
						print(json_encode($paymentIntent));
					}else{
						http_response_code(404);
						print(json_encode(['error' => 'Wrong Id']));
					}
				}else{
					http_response_code(404);
					print(json_encode(['error' => 'Wrong Client Secret']));
				}
				break;
			}
		default:
			{
				http_response_code(404);
				header('Content-Type: application/json; charset=utf-8');
				die(json_encode(['erreur' => 'Action inconnue']));
			}
	}





	// Ajout d'un produit
	//	$produit = $stripe->products->create([
	//		'name' => 'Une autre oeuvre quelconque',
	//		'description' => 'Test pour une oeuvre'
	//	]);
	//	debug("C'est bon, produit créé : ".$produit->id."\n");
	//
	//	// Ajout d'un prix
	//	$prix = $stripe->prices->create([
	//		'unit_amount' => 1200,
	//		'currency' => 'eur',
	//		'recurring' => ['interval' => 'month'],
	//		'product' => $produit->id
	//	]);
	//	debug("C'est bon, prix créé : ".$prix->id."\n");
	// Ajout d'une intention de paiement
	//	$intention = $stripe->paymentIntents->create([
	//		'amount' => 500000,
	//		'currency' => 'eur',
	//		'automatic_payment_methods' => ['enabled' => true]
	//	]);
	//	print(json_encode($intention));
	// Récupération des méthodes de paiement
	//	debug($stripe->paymentMethods->all([
	//		'type' => 'card',
	//		'limit' => 3
	//	]));
	// Création d'un client
	//	$client = $stripe->customers->create([
	//		'name' => 'Michel GILLET',
	//		'email' => 'michel@avalone-fr.com'
	//	]);
	//	debug('Client créé '.$client->id);
	//Création d'une carte de paiement
	//pour les tests
	/**
	 *
	 * Réseau d'émission : Visa
	 * Numéro de carte :4044296766493799
	 * Nom : GILLET Michel
	 * Adresse : Arlington Avenue 36
	 * Pays : Ethiopia
	 * CVV: 713
	 * Exp: 10/2027
	 */
	//	$card = $stripe->customers->createSource('cus_PZHZuQdyYO27rA', [
	//		'source' => 'tok_visa'
	//	]);
	//
	//	debug("Carte créé : ".$card->id);
	//https://docs.stripe.com/payments/accept-a-payment?platform=web&ui=elements
	//https://docs.stripe.com/api/payment_intents/create
	//https://docs.stripe.com/testing?testing-method=card-numbers
	//https://support.stripe.com/questions/enabling-access-to-raw-card-data-apis
	// Récupération des intentions de paiement
	//	$intents = $stripe->paymentIntents->all();
	//	if (count($intents->data)>0){
	//		$intent = $intents->data[0];
	//
	//		$intent->confirm([
	//			'payment_method' => 'pm_card_visa',
	//			'return_url' => 'http://localhost:8081/?section=panier&page=paiement&action=confirm'
	//		]);
	//		debug($intent);
	//	}else{
	//		debug("Pas d'intentions");
	//	}
	//	debug($stripe);