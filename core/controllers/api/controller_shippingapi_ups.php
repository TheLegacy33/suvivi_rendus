<?php
	/**
	 * @var string $section
	 * @var string $action
	 * @var User $userLogged
	 *
	 * Controller pour les appels API UPS
	 *
	 * /**
	 *  Infos UPS
	 *  Votre numéro de compte client : F927C4
	 *
	 *  Votre identifiant de session : Artinter-F927C4
	 *
	 *  Votre mot de passe :  2nV%ahcj;+yvzq 	// ancien Expéditions7C4!
	 *
	 *  Application : artinteractivities
	 *  ID du client : AVCaRbQbRqmGNq0zlRAEiOemffmABsxaSGLAba2t0W6A6kA2
	 *  Password : L4MWwoaMAJXxCgGOlfJtQJFKzUNxzA4SHJJ92yvxLtzuQbwwPrTlG8Qifa4dIWWd
	 *
	 */
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');

	const USER_ID = 'AVCaRbQbRqmGNq0zlRAEiOemffmABsxaSGLAba2t0W6A6kA2';
	const USER_PASS = 'L4MWwoaMAJXxCgGOlfJtQJFKzUNxzA4SHJJ92yvxLtzuQbwwPrTlG8Qifa4dIWWd';
	const CLIENT_ID = 'F927C4';
	const VERSION_API = "v1";

	/**
	 * Constantes
	 */
	// PackageWeight
	const UOM_LBS = 'LBS'; // Pounds (default)
	const UOM_KGS = 'KGS'; // Kilograms

	// Dimensions
	const UOM_IN = 'IN'; // Inches
	const UOM_CM = 'CM'; // Centimeters

	// Dimensions for Locator
	const UOM_MI = 'MI'; // Miles
	const UOM_KM = 'KM'; // Kilometers

	function getNewToken(): ?UPS_Token{
		$curl = curl_init();
		$payload = "grant_type=client_credentials";

		curl_setopt_array($curl, [
			CURLOPT_HTTPHEADER => [
				"Content-Type: application/x-www-form-urlencoded",
				"x-merchant-id: ".CLIENT_ID,
				"Authorization: Basic " . base64_encode(USER_ID.':'.USER_PASS)
			],
			CURLOPT_POSTFIELDS => $payload,
			CURLOPT_URL => "https://wwwcie.ups.com/security/v1/oauth/token",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "POST",
		]);
		$response = curl_exec($curl);
		$upsToken = new UPS_Token();
		if ($response){
			$upsToken->constructFromJson($response);
		}
		$error = curl_error($curl);
		curl_close($curl);
		if ($error == ''){
			return $upsToken;
		}else{
			return null;
		}
	}

	function getCurrentToken(): UPS_Token{
		$upsToken = new UPS_Token();
		if (Session::getActiveSession()->propertyExists('UPS_Token')){
			$upsToken = unserialize(Session::getActiveSession()->getProperty('UPS_Token'));
			if ($upsToken->expired()){
				$upsToken = getNewToken();
			}
		}else{
			$upsToken = getNewToken();
		}
		Session::getActiveSession()->setProperty('UPS_Token', serialize($upsToken));
		return $upsToken;
	}

	function getTimeInTransit(): mixed{
		$curl = curl_init();

		$payload = array(
			"originCountryCode" => "FR",
			"originStateProvince" => "",
			"originCityName" => "MERIGNAC",
			"originTownName" => "",
			"originPostalCode" => "33700",
			"destinationCountryCode" => "US",
			"destinationStateProvince" => "NH",
			"destinationCityName" => "MANCHESTER",
			"destinationTownName" => "",
			"destinationPostalCode" => "03104",
			"weight" => "10.5",
			"weightUnitOfMeasure" => "KGS",
			"shipmentContentsValue" => "10.5",
			"shipmentContentsCurrencyCode" => "EUR",
			"billType" => "03",
			"shipDate" => date('Y-m-d', time() + (24*3600)),
			"shipTime" => "",
			"residentialIndicator" => "",
			"avvFlag" => true,
			"numberOfPackages" => "1"
		);

		curl_setopt_array($curl, [
			CURLOPT_HTTPHEADER => [
				"Authorization: Bearer ".getCurrentToken()->getAccessToken(),
				"Content-Type: application/json",
				"transId: ".uniqid(more_entropy: true),
				"transactionSrc: testing"
			],
			CURLOPT_POSTFIELDS => json_encode($payload),
			CURLOPT_URL => "https://wwwcie.ups.com/api/shipments/".VERSION_API."/transittimes",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "POST",
		]);

		$response = json_decode(curl_exec($curl));
		$error = curl_error($curl);

		curl_close($curl);

		if ($error) {
			return [];
		} else {
			return $response;
		}
	}

	function estimateCosts(): mixed{
		$curl = curl_init();

		$payload = array(
			"currencyCode" => "GBP",
			"transID" => "325467165",
			"allowPartialLandedCostResult" => false,
			"alversion" => 0,
			"shipment" => array(
				"id" => "ShipmentTestFromGBToFR4789",
				"importCountryCode" => "GB",
				"importProvince" => "",
				"shipDate" => date('Y-m-d', time() + (24*3600)),
				"exportCountryCode" => "FR",
				"incoterms" => "",
				"shipmentItems" => array(
					array(
						"commodityId" => "4790",
						"grossWeight" => "10",
						"grossWeightUnit" => "LB",
						"priceEach" => "10",
						"hsCode" => "97039000",
						"quantity" => 1,
						"UOM" => "Kilogram",
						"originCountryCode" => "GB",
						"commodityCurrencyCode" => "EUR",
						"description" => "Sculpture"
					)
				),
				"transModes" => "",
				"shipmentType" => "Sale"
			)
		);

		curl_setopt_array($curl, [
			CURLOPT_HTTPHEADER => [
				"AccountNumber: ".CLIENT_ID,
				"Authorization: Bearer ".getCurrentToken()->getAccessToken(),
				"Content-Type: application/json",
				"transId: ".uniqid(more_entropy: true),
				"transactionSrc: testing"
			],
			CURLOPT_POSTFIELDS => json_encode($payload),
			CURLOPT_URL => "https://wwwcie.ups.com/api/landedcost/".VERSION_API."/quotes",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "POST",
		]);
		$response = json_decode(curl_exec($curl));
		$error = curl_error($curl);
//debug(json_encode($payload));
		curl_close($curl);

		if ($error) {
			return [];
		} else {
			return $response;
		}
	}

	function estimateShipping(Pays $paysOrigine, Pays $paysDestination, Oeuvre $oeuvreALivrer): mixed{
		$artiste = DAOArtiste::getById($oeuvreALivrer->getIdArtiste());

		$requestoption = "Shop";
		$query = array(
			"additionalinfo" => ""
		);

		$curl = curl_init();

		$payload = array(
			"RateRequest" => array(
//				"PickupType" => array(
//					"Code" => "01"
//				),
				"Request" => array(
					"RequestOption" => $requestoption,
					"SubVersion" => "2205",
					"TransactionReference" => array(
						"CustomerContext" => "Livraison d'une oeuvre"
					)
				),
				"Shipment" => array(
					"Shipper" => array(
//						"AttentionName" => "Arts Interactivities",
						"Name" => "Arts Interactivities",
						"ShipperNumber" => "F927C4",
						"Address" => array(
							"AddressLine" => array(
								"5 rue latour"
							),
							"City" => "BORDEAUX",
//							"StateProvinceCode" => "AQ",
							"PostalCode" => "33000",
							"CountryCode" => "FR"
						)
					),
					"ShipFrom" => array( // Informations de l'artiste
//						"AttentionName" => $artiste->getPseudo(),
//						"Name" => $artiste->getUser()->getFullName(),
						"Address" => array(
//							"AddressLine" => array(
//								"152 Avenue Jean-Jaures"
//							),
//							"City" => "PESSAC",
//							"StateProvinceCode" => "AQ",
							"PostalCode" => "96250",
							"CountryCode" => $paysOrigine->getCodeIso2()
						)
					),
					"ShipTo" => array(
//						"AttentionName" => "Michel GILLET",
//						"Name" => "",
						"Address" => array(
//							"ResidentialAddressIndicator" => "true",
//							"AddressLine" => array(
//								"10 Allee des sapins"
//							),
//							"City" => "MERIGNAC",
//							"StateProvinceCode" => "AQ",
							"PostalCode" => "SW1A1AA",
							"CountryCode" => $paysDestination->getCodeIso2()
						)
					),
//					"AlternateDeliveryAddress" => array(
//						"Name" => "Voisin",
//						"Address" => array(
//							"AddressLine" => array(
//								"219 Avenue des eyquems"
//							),
//							"City" => "MERIGNAC",
//							"StateProvinceCode" => "AQ",
//							"PostalCode" => "33700",
//							"CountryCode" => "FR"
//						)
//					),
					"InvoiceLineTotal" => array(
						"CurrencyCode" => "EUR",
						"MonetaryValue" => (string)$oeuvreALivrer->getPrixUnitaire()
					),
					"PaymentDetails" => array(
						"ShipmentCharge" => array(
							array(
								"Type" => "01",
								"BillShipper" => array(
									"AccountNumber" => "F927C4"
								)
							)
						)
					),
					"Service" => array(
						"Code" => "11",
						"Description" => "UPS Standard"
					),
					"NumOfPieces" => "1",
					"Package" => array(
						array(
							"PackageServiceOptions" => array(
//								"InsuredValue" => array(
//									"CurrencyCode" => "EUR",
//									"MonetaryValue" => "00000150"
//								),
//								"DeclaredValue" => array(
//									"CurrencyCode" => "EUR",
//									"MonetaryValue" => "00000150"
//								),
//								"DeliveryConfirmation" => array(
//									"DCISType" => "2"
//								),
//								"VerbalConfirmation" => array(
//									"Name" => "Michel GILLET"
//								)
							),
//							"SimpleRate" => array(
//								"Description" => "SimpleRateDescription",
//								"Code" => "XS"
//							),
							"PackagingType" => array(
								"Code" => "02",
								"Description" => "Packaging"
							),
							"Dimensions" => array(
								"UnitOfMeasurement" => array(
									"Code" => strtoupper($oeuvreALivrer->getCaracteristique(DAOCaracteristique::getByLibelle('Hauteur'))->getUnite()->getAbregeMesure()),
									"Description" => $oeuvreALivrer->getCaracteristique(DAOCaracteristique::getByLibelle('Hauteur'))->getUnite()->getLibelle()
								),
								"Length" => $oeuvreALivrer->getCaracteristique(DAOCaracteristique::getByLibelle('Epaisseur'))->getValeur(),
								"Width" => $oeuvreALivrer->getCaracteristique(DAOCaracteristique::getByLibelle('Largeur'))->getValeur(),
								"Height" => $oeuvreALivrer->getCaracteristique(DAOCaracteristique::getByLibelle('Hauteur'))->getValeur()
							),
							"PackageWeight" => array(
								"UnitOfMeasurement" => array(
									"Code" => UOM_KGS, //strtoupper($oeuvreALivrer->getCaracteristique(DAOCaracteristique::getByLibelle('Poids'))->getUnite()->getLibelle()),
									"Description" => $oeuvreALivrer->getCaracteristique(DAOCaracteristique::getByLibelle('Poids'))->getUnite()->getAbregeMesure()
								),
								"Weight" => $oeuvreALivrer->getCaracteristique(DAOCaracteristique::getByLibelle('Poids'))->getValeur()
							)
						)
					),
//					"ShipmentTotalWeight" => array(
//						"Weight" => "1",
//						"UnitOfMeasurement" => array(
//							"Code" => "KGS",
//							"Description" => "Kilograms"
//						)
//					),
					"ShipmentServiceOptions" => array(
//						"DeliveryConfirmation" => array(
//							"DCISType" => "2"
//						),
//						"SundayDeliveryIndicator" => "0",
//						"SaturdayDeliveryIndicator" => "0",
//						"SaturdayPickupIndicator" => "0",
//						"AvailableServicesOption" => "0"
					)
				)
			)
		);

		curl_setopt_array($curl, [
			CURLOPT_HTTPHEADER => [
				"Authorization: Bearer ".getCurrentToken()->getAccessToken(),
				"Content-Type: application/json",
				"transId: ".uniqid(more_entropy: true),
				"transactionSrc: testing"
			],
			CURLOPT_POSTFIELDS => json_encode($payload),
			CURLOPT_URL => "https://wwwcie.ups.com/api/rating/".VERSION_API."/".$requestoption."?" . http_build_query($query),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "POST",
		]);
//debug(json_encode($payload));

		$response = json_decode(curl_exec($curl));
		$error = curl_error($curl);

		curl_close($curl);

		if ($error != '') {
			return $error;
		} else {
			return $response;
		}
	}

	function setShipping(): mixed{
		$query = array(
			"additionaladdressvalidation" => ""
		);

		$curl = curl_init();

		$payload = array(
			"ShipmentRequest" => array(
				"Request" => array(
					"SubVersion" => "1801",
					"RequestOption" => "nonvalidate",
					"TransactionReference" => array(
						"CustomerContext" => "F927C4"
					)
				),
				"Shipment" => array(
					"Description" => "Ship WS test",
					"Shipper" => array(
						"Name" => "artinteractivities",
						"AttentionName" => "ShipperZs Attn Name",
						"TaxIdentificationNumber" => "123456",
						"Phone" => array(
							"Number" => "1115554758",
							"Extension" => " "
						),
						"ShipperNumber" => "F927C4",
						"FaxNumber" => "8002222222",
						"EmailAddress" => 'art-interactivities@gmail.com',
						"Address" => array(
							"AddressLine" => array(
								"5 rue Latour"
							),
							"City" => "BORDEAUX",
							"StateProvinceCode" => "",
							"PostalCode" => "33000",
							"CountryCode" => "FR"
						)
					),
					"ShipTo" => array(
						"Name" => "MG",
						"AttentionName" => "1160b_74",
						"Phone" => array(
							"Number" => "9225377171"
						),
						"EmailAddress" => "michel@devatom.net",
						"Address" => array(
							"AddressLine" => array(
								"10 Allee des sapins"
							),
							"City" => "MERIGNAC",
							"StateProvinceCode" => "",
							"PostalCode" => "33700",
							"CountryCode" => "FR",
							"ResidentialAddressIndicator" => ""
						)
					),
					"ShipFrom" => array(
						"Name" => "Arts",
						"AttentionName" => "1160b_74",
						"Phone" => array(
							"Number" => "1234567890"
						),
						"FaxNumber" => "1234567890",
						"Address" => array(
							"AddressLine" => array(
								"152 Avenue Jean-Jaures"
							),
							"City" => "PESSAC",
							"StateProvinceCode" => "",
							"PostalCode" => "33600",
							"CountryCode" => "FR"
						)
					),
					"PaymentInformation" => array(
						"ShipmentCharge" => array(
							"Type" => "01",
							"BillShipper" => array(
								"AccountNumber" => "F927C4"
							)
						)
					),
					"Service" => array(
						"Code" => "03",
						"Description" => "Express"
					),
					"Package" => array(
						"Description" => "Une oeuvre",
						"Packaging" => array(
							"Code" => "02",
							"Description" => "Nails"
						),
						"Dimensions" => array(
							"UnitOfMeasurement" => array(
								"Code" => "CM",
								"Description" => "Centimeters"
							),
							"Length" => "20",
							"Width" => "40",
							"Height" => "5"
						),
						"PackageWeight" => array(
							"UnitOfMeasurement" => array(
								"Code" => "KG",
								"Description" => "Killograms"
							),
							"Weight" => "5"
						)
					)
				),
				"LabelSpecification" => array(
					"LabelImageFormat" => array(
						"Code" => "GIF",
						"Description" => "GIF"
					),
					"HTTPUserAgent" => "Mozilla/4.5"
				)
			)
		);

		curl_setopt_array($curl, [
			CURLOPT_HTTPHEADER => [
				"Authorization: Bearer ".getCurrentToken()->getAccessToken(),
				"Content-Type: application/json",
				"transId: ".uniqid(more_entropy: true),
				"transactionSrc: testing"
			],
			CURLOPT_POSTFIELDS => json_encode($payload),
			CURLOPT_URL => "https://wwwcie.ups.com/api/shipments/".VERSION_API."/ship?" . http_build_query($query),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "POST",
		]);

		$response = json_decode(curl_exec($curl));
		$error = curl_error($curl);

		curl_close($curl);

		if ($error != '') {
			return $error;
		} else {
			return $response;
		}
	}

	switch ($action){
		case 'getToken':{
			$upsToken = new UPS_Token();
			if (Session::getActiveSession()->propertyExists('UPS_Token')){
				$upsToken = unserialize(Session::getActiveSession()->getProperty('UPS_Token'));
				if ($upsToken->expired()){
					$upsToken = getNewToken();
				}
			}else{
				$upsToken = getNewToken();
			}
			if (!is_null($upsToken)){
				Session::getActiveSession()->setProperty('UPS_Token', serialize($upsToken));
				http_response_code(200);
				print(json_encode(['result' => 'success', 'returnvalue' => $upsToken]));
			}else{
				http_response_code(500);
				print(json_encode(['result' => 'error']));
			}

			break;
		}

		case 'calculDelay':{
			if (!empty($_POST)){
				$retVal = getTimeInTransit();
				http_response_code(200);
				print(json_encode(['result' => 'success', 'returnvalue' => $retVal]));
			}else{
				http_response_code(500);
				print(json_encode(['delai' => 0, 'result' => 'error']));
			}
			break;
		}

		case 'estimateShipping':{
			// Estimation des frais de ports
			if (!empty($_POST)){
				$nomPaysOrigine = trim($_POST['paysOrigine'] ?? 'FRANCE');
				$nomPaysDestination = trim($_POST['paysDestination'] ?? 'FRANCE');
				$idOeuvre = $_POST['idOeuvre'] ?? 0;

				$paysOrigine = DAOPays::getByNom($nomPaysOrigine);
				$paysDestination = DAOPays::getByNom($nomPaysDestination);
				$oeuvreALivrer = DAOOeuvre::getById($idOeuvre);
//debug($oeuvreALivrer->getCaracteristique(DAOCaracteristique::getByLibelle('Hauteur')));
				$retVal = estimateShipping($paysOrigine, $paysDestination, $oeuvreALivrer);
//				debug($retVal);
				http_response_code(200);
				print(json_encode(['result' => 'success', 'returnvalue' => $retVal]));
			}else{
				http_response_code(500);
				print(json_encode(['montantfdp' => 0, 'result' => 'error']));
			}
			break;
		}

		case 'defineShipping':{
			if (!empty($_POST)){
				$retVal = setShipping();
				http_response_code(200);
				print(json_encode(['result' => 'success', 'returnvalue' => $retVal]));
			}else{
				http_response_code(500);
				print(json_encode(['delai' => 0, 'result' => 'error']));
			}
			break;
		}

		case 'calculFdp':{
			if (!empty($_POST)){
				$retVal = estimateCosts();
				http_response_code(200);
				print(json_encode(['result' => 'success', 'returnvalue' => $retVal]));
			}else{
				http_response_code(500);
				print(json_encode(['delai' => 0, 'result' => 'error']));
			}
			break;
		}

		default:{
		}
	}
