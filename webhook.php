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


use \Project\Bdd;
require_once 'class/Bdd.php';
use \Project\Toornament;
require_once 'class/Toornament.php';
use \Project\ManageRegistration;
require_once 'class/ManageRegistration.php';

require_once 'functions/log.php';

try
{
    $BDD = new Bdd('');
    $api_configuration = $BDD->get_toornament();
    $TOORNAMENT = new Toornament($api_configuration);
}
catch (Exception $e)
{
    log_to_file($e->getMessage());
    die();
}

if(isset(getallheaders()['X-Webhook-Secret']))
{
    try
    {
        $BDD->patch_webhook_secret(getallheaders()['X-Webhook-Secret']);
        header('HTTP/1.1 200 OK');
        header('X-Webhook-Secret: '.getallheaders()['X-Webhook-Secret']);
    }
    catch(Exception $e)
    {
        log_to_file("GET : " . $e->getMessage());
        die();
    }
}
else if($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $POST_body = file_get_contents('php://input');

    try
    {
        if(isset(getallheaders()['X-Webhook-Signature']))
        {
            $hash_signature = hash('sha256', $POST_body.$BDD->get_toornament()["webhook_secret"]);
            if($hash_signature !== getallheaders()['X-Webhook-Signature'])
            {
                throw new Exception("Incorrect X-Webhook-Signature");
            }
        }
        else
        {
            throw new Exception("No X-Webhook-Signature");
        }

        $webhook = json_decode($POST_body);

        $MANAGE_REGISTRATION = new ManageRegistration($TOORNAMENT->get_tournament_info()["body"], $BDD->get_requirement(), $BDD->get_setting());

        if(($webhook->name === "registration.created" || $webhook->name === "registration.info_updated") && $webhook->object_type === "registration" && $webhook->scope === "tournament")
        {
            $registration = $TOORNAMENT->get_registrationById($webhook->object_id)["body"];
        }
        else
        {
            throw new Exception("Incorrect JSON registration");
        }

        if($registration->status === "pending")
        {
            $status = $MANAGE_REGISTRATION->status_registration($registration);

            switch($status)
            {
                case "accepted":
                    $TOORNAMENT->patch_registration($registration->id, "accepted");
                    break;
                case "refused":
                    $TOORNAMENT->patch_registration($registration->id, "refused");
                    break;
                case "ignored":
                    break;
            }

            log_to_file("POST : ".$registration->name." -> ".$status);
        }
    }
    catch(Exception $e)
    {
        log_to_file("POST : " . $e->getMessage());
        die();
    }
}
else
{
    log_to_file("UNKNOWN REQUEST");
}