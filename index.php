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

session_start();

use \Project\Bdd;
require_once 'class/Bdd.php';
use \Project\Toornament;
require_once 'class/Toornament.php';

require_once 'functions/csrf.php';

require_once 'functions/messages.php';
$message = get_message();

try
{
    $BDD = new Bdd('');
    $api_configuration = $BDD->get_toornament();
    $requirement = $BDD->get_requirement();
    $setting = $BDD->get_setting();
    $TOORNAMENT = new Toornament($api_configuration);
}
catch (Exception $e)
{
    echo "Error : " . $e->getMessage();
    die();
}

$info = array();
$status = array();
$custom_fields = array();

try
{
    $status += ["toornament_status" => $TOORNAMENT->get_token('test')["http_code"]];
}
catch(Exception $e)
{
    $status += ["toornament_status" => $e->getMessage()];
}

try
{
    $toornamentWebhook = $TOORNAMENT->get_webhook();

    if(count($toornamentWebhook["body"]) > 0)
    {
        foreach($toornamentWebhook["body"] as &$webhook)
        {
            if($webhook->name === $api_configuration["webhook_name"] && $webhook->url === $api_configuration["webhook_url"] && $webhook->enabled === true)
            {
                $toornamentSubscription = $TOORNAMENT->get_subscription($webhook->id);
                foreach($toornamentSubscription["body"] as &$subscription)
                {
                    if($subscription->scope_id === $TOORNAMENT->getToornamentId())
                    {
                        $status_subscription[$subscription->event_name] = true;
                    }
                }
                if(isset($status_subscription) && $status_subscription["registration.created"] === true && $status_subscription["registration.info_updated"] === true)
                {
                    $status += ["webhook" => true];
                }
            }
        }
        if(!isset($status["webhook"]))
        {
            $status += ["webhook" => false];
        }
    }
    else
    {
        $status += ["webhook" => false];
    }

    $status += ["api_configuration" => $toornamentWebhook["http_code"]];
}
catch(Exception $e)
{
    $status += ["api_configuration" => $e->getMessage()];
}

try
{
    $toornamentInfo = $TOORNAMENT->get_tournament_info();
    $dt_now = new DateTime();
    $dt_now->setTimezone(new DateTimeZone('Europe/Paris'));

    $dt_opening_datetime = new DateTime($toornamentInfo["body"]->registration_opening_datetime, new DateTimeZone('UTC'));
    $dt_opening_datetime->setTimezone(new DateTimeZone('Europe/Paris'));
    $dt_closing_datetime = new DateTime($toornamentInfo["body"]->registration_closing_datetime, new DateTimeZone('UTC'));
    $dt_closing_datetime->setTimezone(new DateTimeZone('Europe/Paris'));

    $no_date = strtotime($dt_now->format('Y-m-d H:i:s')) === strtotime($dt_closing_datetime->format('Y-m-d H:i:s'));

    $info += [
        "name" => $toornamentInfo["body"]->name,
        "size" => $toornamentInfo["body"]->size,
        "registration_enabled" => $toornamentInfo["body"]->registration_enabled,
        "open" => strtotime($dt_now->format('Y-m-d H:i:s')) <= strtotime($dt_closing_datetime->format('Y-m-d H:i:s')),
        "registration_opening_datetime" => $no_date ? "No date specified" : $dt_opening_datetime->format('l j F Y - H:i:s'),
        "registration_closing_datetime" => $no_date ? "No date specified" : $dt_closing_datetime->format('l j F Y - H:i:s'),
        "http_code" => $toornamentInfo["http_code"]
    ];
}
catch(Exception $e)
{
    $info += ["http_code" => $e->getMessage()];
}

try
{
    $toornamentRegistration = $TOORNAMENT->get_registration();
    $refused = 0;
    $accepted = 0;
    $pending = 0;

    foreach($toornamentRegistration["body"] as &$registration)
    {
        if($registration->status === "refused")
        {
            $refused++;
        }
        elseif($registration->status === "accepted")
        {
            $accepted++;
        }
        elseif($registration->status === "pending")
        {
            $pending++;
        }
    }

    $info += [
        "refused" => $refused,
        "accepted" => $accepted,
        "pending" => $pending
    ];
}
catch(Exception $e)
{
    $info += ["http_code" => $e->getMessage()];
}

try
{
    $toornamentCustom_fields = $TOORNAMENT->get_custom_fields();
    $custom_fields["custom_fields"] = [];

    foreach($toornamentCustom_fields["body"] as &$custom_field)
    {
        array_push($custom_fields["custom_fields"], $custom_field->machine_name.' ('.$custom_field->target_type.')');
    }
    $custom_fields["http_code"] = $toornamentCustom_fields["http_code"];
}
catch(Exception $e)
{
    $custom_fields += ["http_code" => $e->getMessage()];
}

//Load Twig
require_once 'vendor/autoload.php';
$loader = new Twig_Loader_Filesystem('templates/');
$twig = new Twig_Environment($loader, array('debug' => true));

echo $twig->render('index.twig', array(
    'index_path' => '',
    'images_path' => '',
    'css_path' => '',
    'js_path' => '',
    'messages' => $message,
    'status' => $status,
    'info' => $info,
    'custom_fields' => $custom_fields,
    'api_configuration' => $api_configuration,
    'requirement' => $requirement,
    'setting' => $setting,
    'csrf_apiConfiguration' => new_crsf('csrf_apiConfiguration'),
    'csrf_requirement' => new_crsf('csrf_requirement')
));