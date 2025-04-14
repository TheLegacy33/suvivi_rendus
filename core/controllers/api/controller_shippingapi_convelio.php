<?php
	/**
	 * @var string $section
	 * @var string $action
	 * @var User $userLogged
	 *
	 * Controller pour les appels API convelio
	 *
	 * Doc : https://developers.convelio.com/index.html#operation/estimateShipppingPrice
	 *  Infos Convelio
	 *  Votre numéro de compte client :
	 *
	 *  Votre identifiant de session :
	 *
	 *  Votre mot de passe :
	 *
	 *  Application : artinteractivities
	 *  ID du client :
	 *  Password :
	 *
	 *  secret-key-live :
	 *  secret-key-test :
	 *	sandbox-url : https://api.sandbox.convelio.com/v2/
	 *  Production-url : https://api.convelio.com/v2/
	 */
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');

	const USER_ID = 'AVCaRbQbRqmGNq0zlRAEiOemffmABsxaSGLAba2t0W6A6kA2';
	const USER_PASS = 'L4MWwoaMAJXxCgGOlfJtQJFKzUNxzA4SHJJ92yvxLtzuQbwwPrTlG8Qifa4dIWWd';
	const CLIENT_ID = 'F927C4';
	const VERSION_API = "v1";

	const SECRET_KEY = '';
	const API_BASE_URL = 'https://api.sandbox.convelio.com/v2/';
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

	function estimatePrice(Pays $paysOrigine, Pays $paysDestination): mixed{
		$curl = curl_init();

		$payload = array(
			"delivery" => array(
				"type" => "curbside",
				"address" => array(
					"street" => "42 rue des allees",
					"city" => "Paris",
					"state" => "Ile-de-france",
					"postcode" => "75004",
					"country_code" => "FR"
				),
				"contact" => array(
					"first_name" => "John",
					"last_name" => "Doe",
					"email" => "john.doe@exemple.com",
					"phone" => "+442033188673",
					"additional_emails" => array(
						"user@example.com"
					),
					"additional_phones" => array(
						"+442033188673"
					)
				),
				"company_name" => "Company Name LTD",
    			"additional_info" => "Additional information regarding the delivery."
			),
			"shipping_speed" => "regular_speed",
			"direct_label_request" => "cheapest_option",
			"contract_insurance" => false,
			"pickups" => array(
				array(
					"company_name" => "Company Name LTD",
					"address" => array(
						"street" => "42 rue des allees",
						"city" => "Paris",
						"state" => "Ile-de-france",
						"postcode" => "75004",
						"country_code" => "FR"
					),
					"contact" => array(
						"first_name" => "John",
						"last_name" => "Doe",
						"email" => "john.doe@exemple.com",
						"phone" => "+442033188673",
						"additional_emails" => array(
							"user@example.com"
						),
						"additional_phones" => array(
							"+442033188673"
						)
					),
					"items" => array(
						array(
							"name" => "Vase ming",
							"description" => "Vase ming XIV",
							"quantity" => 1,
							"current_packing" => "not_packed",
							"desired_packing" => "masterpack",
							"measurement_system" => "us",
							"type" => "other.other",
							"materials" => array(
								"stone",
								"glass"
							),
							"value" => array(
								"amount" => 15000,
								"currency_code" => "EUR"
							),
							"length" => 100,
							"height" => 100,
							"width" => 100,
							"weight" => 100
						),
					),
					"additional_info" => "Additional information regarding the pickup."
				)
			),
		);

		curl_setopt_array($curl, [
			CURLOPT_HTTPHEADER => [
				"Authorization: ".SECRET_KEY,
				"Content-Type: application/json",
				"Accept: application/json"
			],
			CURLOPT_POSTFIELDS => json_encode($payload),
			CURLOPT_URL => API_BASE_URL."shipping/estimate/price",
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

	switch ($action){
		case 'estimatePrice':{
			// Estimation des frais de ports
			if (!empty($_POST)){
				$nomPaysOrigine = trim($_POST['paysOrigine'] ?? 'FRANCE');
				$nomPaysDestination = trim($_POST['paysDestination'] ?? 'FRANCE');
				$idOeuvre = $_POST['idOeuvre'] ?? 0;

				$paysOrigine = DAOPays::getByNom($nomPaysOrigine);
				$paysDestination = DAOPays::getByNom($nomPaysDestination);
				$oeuvreALivrer = DAOOeuvre::getById($idOeuvre);
//debug($oeuvreALivrer->getCaracteristique(DAOCaracteristique::getByLibelle('Hauteur')));
//				$retVal = estimatePrice($paysOrigine, $paysDestination, $oeuvreALivrer);
				$retVal = estimatePrice($paysOrigine, $paysDestination);
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
