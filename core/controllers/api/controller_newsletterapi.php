<?php

/**
 * @var string $section
 * @var string $action
 *
 * Controller pour les appels API
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
switch ($action) {
    case 'checkEmailNewsletter': {
            $email = htmlentities($_GET['emailNewsletter']);
            if (!DAONewsletter::checkEmail($email)) {
                http_response_code(200);
                print(json_encode(['NewsletterUpdate' => true, 'emailExists' => false, 'result' => 'success']));
            } else {
                http_response_code(500);
                print(json_encode(['NewsletterUpdate' => false, 'emailExists' => $email, 'result' => 'error']));
            }
            break;
        }
    case 'getNewsletter': {

            $email = htmlentities($_POST['emailNewsletter']);

            $prenom = htmlentities($_POST['prenomNewsletter']);

            $nom = htmlentities($_POST['nomNewsletter']);

            $categorie = json_decode($_POST['tabCat']);


            $artisteChoix = $_POST['artisteChoix'] ?? '';
            if (BDD::openTransaction()) {
                $ajout = false;

                $uneNewsletter = new Newsletter($email, $prenom, $nom);
                $uneNewsletter->setArtisteChoix($artisteChoix);

                foreach ($categorie as $uneCat) {

                    $uneNewsletter->addCategorieNewsletter(DAOCategorieNewsletter::getById(intval($uneCat)));
                }
                $ajout = true;

                if ($ajout) {
                    $error = !DAONewsletter::insert($uneNewsletter);
                }

                if (!$error) {
                    http_response_code(200);
                    print(json_encode(['NewsletterUpdate' => true, 'updateNewsletter' => true, 'result' => 'success']));
                    BDD::commitTransaction();
                    //BDD::rollbackTransaction();
                } else {
                    http_response_code(500);
                    print(json_encode(['NewsletterUpdate' => false, 'updateNewsletter' => false, 'result' => 'error']));
                    BDD::rollbackTransaction();
                }
            }

            break;
        }
    case 'recupNewsletter': {



            $lesNewsletter = DAONewsletter::getAll();

            if (count($lesNewsletter) != 0) {

                http_response_code(200);

                print(json_encode(['newsletter' => true, 'resultData' => $lesNewsletter, 'result' => 'success']));
            } else {
                http_response_code(500);
                print(json_encode(['newsletter' => false, 'resultData' => false, 'result' => 'error']));
            }
            break;
        }
    case 'other': {
            break;
        }
    default: {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            die(json_encode(['erreur' => 'Action inconnue']));
        }
}
