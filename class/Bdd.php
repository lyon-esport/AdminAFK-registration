<?php
// ----------------------------------------------------------------------------
// Copyright © Lyon e-Sport, 2019
//
// Contributeur(s):
//     * Ortega Ludovic - ludovic.ortega@lyon-esport.fr
//
// Ce logiciel, AdminAFK-registration, est un programme informatique servant à gérer
// automatiquement les inscriptions des joueurs/équipes en fonction de conditions sur
// la plateforme toornament.
//
// Ce logiciel est régi par la licence CeCILL soumise au droit français et
// respectant les principes de diffusion des logiciels libres. Vous pouvez
// utiliser, modifier et/ou redistribuer ce programme sous les conditions
// de la licence CeCILL telle que diffusée par le CEA, le CNRS et l'INRIA
// sur le site "http://www.cecill.info".
//
// En contrepartie de l'accessibilité au code source et des droits de copie,
// de modification et de redistribution accordés par cette licence, il n'est
// offert aux utilisateurs qu'une garantie limitée.  Pour les mêmes raisons,
// seule une responsabilité restreinte pèse sur l'auteur du programme,  le
// titulaire des droits patrimoniaux et les concédants successifs.
//
// A cet égard  l'attention de l'utilisateur est attirée sur les risques
// associés au chargement,  à l'utilisation,  à la modification et/ou au
// développement et à la reproduction du logiciel par l'utilisateur étant
// donné sa spécificité de logiciel libre, qui peut le rendre complexe à
// manipuler et qui le réserve donc à des développeurs et des professionnels
// avertis possédant  des  connaissances  informatiques approfondies.  Les
// utilisateurs sont donc invités à charger  et  tester  l'adéquation  du
// logiciel à leurs besoins dans des conditions permettant d'assurer la
// sécurité de leurs systèmes et ou de leurs données et, plus généralement,
// à l'utiliser et l'exploiter dans les mêmes conditions de sécurité.
//
// Le fait que vous puissiez accéder à cet en-tête signifie que vous avez
// pris connaissance de la licence CeCILL, et que vous en avez accepté les
// termes.
// ----------------------------------------------------------------------------


namespace Project;

use PDO;
use Exception;

class Bdd
{
    private $PATH = "";
    private $BDD = null;

    public function __construct($path = "")
    {
        $this->PATH = $path;
        if(!file_exists ($this->PATH.'database.sqlite'))
        {
            throw new Exception("Database doesn't exist !");
        }
        $this->BDD = new PDO('sqlite:' . $this->PATH . 'database.sqlite');
        $this->BDD->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function get_toornament()
    {
        $stmt = $this->BDD->prepare('
                                              SELECT api_key, client_id, client_secret, toornament_id, webhook_name, webhook_url, webhook_secret 
                                              FROM toornament 
                                              WHERE id = 1
                                              ');
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $result[0];
    }

    public function patch_toornament($client_id = "", $client_secret = "", $api_key = "", $toornament_id = "", $webhook_name = "", $webhook_url = "")
    {
        $stmt = $this->BDD->prepare('
                                              UPDATE toornament 
                                              SET api_key = :api_key, client_id = :client_id, client_secret = :client_secret, toornament_id = :toornament_id, webhook_name = :webhook_name, webhook_url = :webhook_url 
                                              WHERE id = 1
                                              ');
        $stmt->execute(array(':api_key' => $api_key, ':client_id' => $client_id, ':client_secret' => $client_secret, ':toornament_id' => $toornament_id, ':webhook_name' => $webhook_name, ':webhook_url' => $webhook_url));
        $stmt->closeCursor();
    }

    public function patch_webhook_secret($webhook_secret = "")
    {
        $stmt = $this->BDD->prepare('
                                              UPDATE toornament 
                                              SET webhook_secret = :webhook_secret 
                                              WHERE id = 1
                                              ');
        $stmt->execute(array(':webhook_secret' => $webhook_secret));
        $stmt->closeCursor();
    }

    public function delete_webhook_secret()
    {
        $stmt = $this->BDD->prepare('
                                              UPDATE toornament 
                                              SET webhook_secret = "" 
                                              WHERE id = 1
                                              ');
        $stmt->closeCursor();
    }

    public function get_requirement()
    {
        $stmt = $this->BDD->prepare('
                                              SELECT name, value, match, custom_field, type 
                                              FROM requirement
                                              ');
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $result;
    }

    public function patch_requirement($name = "", $value = "", $match = "", $custom_field = "", $type = "")
    {
        $stmt = $this->BDD->prepare('
                                                UPDATE requirement 
                                                SET value = :value, match = :match, custom_field = :custom_field, type = :type
                                                WHERE name = :name
                                                ');
        $stmt->execute(array(':value' => $value, ':match' => $match, ':custom_field' => $custom_field, ':type' => $type, ':name' => $name));
        $stmt->closeCursor();
    }

    public function get_setting()
    {
        $stmt = $this->BDD->prepare('
                                              SELECT name, value 
                                              FROM setting
                                              ');
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $result;
    }

    public function patch_setting($name, $value)
    {
        $stmt = $this->BDD->prepare('
                                                UPDATE setting 
                                                SET value = :value
                                                WHERE name = :name
                                                ');
        $stmt->execute(array(':value' => $value, ':name' => $name));
        $stmt->closeCursor();
    }
}