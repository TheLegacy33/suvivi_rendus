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
    case 'updateArticleUser': {
            $idUser = $_POST['idUser'];
            $user = DAOUser::getById($idUser);
            $sousCategorie = htmlentities($_POST['sousCategorie']);
            $sousSousCategorie[] = intval($_POST['sousSousCategorie']);

            $titreArticle = htmlentities($_POST['titreArticle']);
            $paragrapheIntroArticle = htmlentities($_POST['paragrapheIntroArticle']);
            $dureeArticle = intval($_POST['dureeArticle'] ?? '0');
            $sourceTitre = htmlentities($_POST['sourceTitre']);
            $sourceNomArtiste = htmlentities($_POST['sourceNomArtiste']);
            $sourceLieuOeuvre = htmlentities($_POST['sourceLieuOeuvre']);
            $sourceAnneeCreation = htmlentities($_POST['sourceAnneeCreation']);
            $sourceLienOeuvre = htmlentities($_POST['sourceLienOeuvre']);
            if (BDD::openTransaction()) {
                $ajout = false;

                if (count($user->getArticle()) < 1) {
                    $unArticle = new Article($titreArticle, $paragrapheIntroArticle, $sourceTitre, $sourceNomArtiste, $sourceLieuOeuvre, $sourceAnneeCreation, $sourceLienOeuvre, $dureeArticle, DAOStatutArticle::getById(1));
                    $unArticle->setSousSousCategorieArticle($sousSousCategorie);
                    $unArticle->setIdUser($user->getId());
                    $imgIntroArticle = $_FILES['imgArticle'];

                    if (!is_null($imgIntroArticle)) {

                        $sendIntroImage = true;
                        $introImageSend = new AIImage($imgIntroArticle['name'], $imgIntroArticle['full_path'], $imgIntroArticle['tmp_name'], $imgIntroArticle['error'], $imgIntroArticle['size'], $imgIntroArticle['type']);

                        if ($introImageSend->getSize() > 0) {
                            $introImageSend->setLocalFilePath($user->getPersonalFolder() . 'articles/');
                            if ($introImageSend->moveFile(false, false)) {

                                $unArticle->setCheminPhoto($introImageSend->getFullPath());
                            }
                        }
                    }
                    $ajout = true;
                }

                if ($ajout) {
                    $error = !DAOArticle::insert($unArticle);
                }

                if (!$error) {
                    http_response_code(200);
                    print(json_encode(['userArticleUpdate' => $user->getId(), 'articleUpdated' => true, 'result' => 'success']));
                    BDD::commitTransaction();
                    //BDD::rollbackTransaction();
                } else {
                    http_response_code(500);
                    print(json_encode(['userArticleUpdate' => false, 'articleUpdated' => false, 'result' => 'error']));
                    BDD::rollbackTransaction();
                }
            }
            break;
        }
    case 'updateArticleContenuUser': {
            $idUser = $_POST['idUser'];
            $user = DAOUser::getById($idUser);
            $testContenue = htmlentities($_POST['contenueArticle']);
            if (BDD::openTransaction()) {
                $uneSection = new SectionArticle("", htmlspecialchars($testContenue), 1);
                $uneSection->setContenusSection([]);
				$uneSection->setIdArticle(38);
                $error = !DAOSectionArticle::insert($uneSection);
                if (!$error) {
                    http_response_code(200);
                    print(json_encode(['userArticleUpdate' => $user->getId(), 'articleUpdated' => true, 'result' => 'success']));
                    //BDD::commitTransaction();
                    BDD::rollbackTransaction();
                } else {
                    http_response_code(500);
                    print(json_encode(['userArticleUpdate' => false, 'articleUpdated' => false, 'result' => 'error']));
                    BDD::rollbackTransaction();
                }
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
