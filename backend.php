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
use \Project\ManageRegistration;
require_once 'class/ManageRegistration.php';

require_once 'functions/csrf.php';
require_once 'functions/messages.php';

try
{
    $BDD = new Bdd('');
    $api_configuration = $BDD->get_toornament();
    $TOORNAMENT = new Toornament($api_configuration);
}
catch (Exception $e)
{
    create_message([['title' => 'Error !', 'content' => 'An error occurred when processing your request', 'color' => 'error', 'delete' => true, 'container' => true]]);
    header('Location: index.php');
    die();
}

if(isset($_POST['choice']) && !empty($_POST['choice']))
{
    if($_POST['choice'] === "check-registration")
    {
        check_registration($BDD, $TOORNAMENT);
    }
    elseif ($_POST['choice'] === "save-api_configuration")
    {
        save_apiConfiguration($BDD, $TOORNAMENT);
    }
    elseif ($_POST['choice'] === "save-requirements")
    {
        save_requirement($BDD);
    }
    elseif ($_POST['choice'] === "purge-webhook")
    {
        purge_webhook($BDD, $TOORNAMENT);
    }
    else
    {
        create_message([['title' => 'Error !', 'content' => 'An error occurred when processing your request', 'color' => 'error', 'delete' => true, 'container' => true]]);
        header('Location: index.php');
        die();
    }
}
else
{
    create_message([['title' => 'Error !', 'content' => 'An error occurred when processing your request', 'color' => 'error', 'delete' => true, 'container' => true]]);
    header('Location: index.php');
    die();
}

/**
 * @param Bdd $BDD
 * @param Toornament $TOORNAMENT
 */
function check_registration($BDD, $TOORNAMENT)
{
    try
    {
        $MANAGE_REGISTRATION = new ManageRegistration($TOORNAMENT->get_tournament_info()["body"], $BDD->get_requirement(), $BDD->get_setting());
        $registrations = $TOORNAMENT->get_registration()["body"];

        $pending_registration = false;
        $nb_registrations = count($registrations);
        $nb_accepted = 0;
        $nb_refused = 0;
        $nb_ignored = 0;

        foreach($registrations as $registration)
        {
            if($registration->status === "pending")
            {
                $pending_registration = true;

                $status = $MANAGE_REGISTRATION->status_registration($registration);

                switch($status)
                {
                    case "accepted":
                        $TOORNAMENT->patch_registration($registration->id, "accepted");
                        $nb_accepted++;
                        break;
                    case "refused":
                        $TOORNAMENT->patch_registration($registration->id, "refused");
                        $nb_refused++;
                        break;
                    case "ignored":
                        $nb_ignored++;
                        break;
                }
            }
        }
        if($pending_registration)
        {
            create_message([['title' => 'Success !', 'content' => $nb_registrations.' registrations : '.$nb_accepted.' accepted, '.$nb_refused.' refused, '.$nb_ignored.' ignored', 'color' => 'success', 'delete' => true, 'container' => true]]);
        }
        else
        {
            create_message([['title' => 'Error !', 'content' => 'No pending registration to manage', 'color' => 'error', 'delete' => true, 'container' => true]]);
        }

        header('Location: index.php');
        die();
    }
    catch(Exception $e)
    {
        create_message([['title' => 'Error !', 'content' => $e->getMessage(), 'color' => 'error', 'delete' => true, 'container' => true]]);
        header('Location: index.php');
        die();
    }
}

/**
 * @param Bdd $BDD
 * @param Toornament $TOORNAMENT
 */
function save_apiConfiguration($BDD, $TOORNAMENT)
{
    if(!check_csrf('csrf_apiConfiguration'))
    {
        create_message([['title' => 'Error !', 'content' => 'An error occurred when processing your request', 'color' => 'error', 'delete' => true, 'container' => true]]);
        header('Location: index.php');
        die();
    }

    $fields = [
        "client_id" => ["type" => "alphanumeric"],
        "client_secret" => ["type" => "alphanumeric"],
        "api_key" => ["type" => "string"],
        "toornament_id" => ["type" => "numeric"],
        "webhook_name" => ["type" => "string_30"],
        "webhook_url" => ["type" => "url"]
    ];

    try
    {
        foreach($fields as $key => $value)
        {
            if(isset($_POST[$key]))
            {
                switch($fields[$key]["type"])
                {
                    case "alphanumeric":
                        $check = ctype_alnum($_POST[$key]);
                        break;
                    case "numeric":
                        $check = ctype_digit($_POST[$key]);
                        break;
                    case "string":
                        $check = preg_match_all("/^[0-9a-zA-Z_\-]*$/", $_POST[$key]);
                        break;
                    case "string_30":
                        $check = preg_match_all("/^[0-9a-zA-Z_\- ]{1,30}$/", $_POST[$key]);
                        break;
                    case "url":
                        $check = filter_var($_POST[$key], FILTER_VALIDATE_URL);
                        break;
                    default:
                        throw new Exception("An error occurred when processing your request");
                        break;
                }

                if(empty($_POST[$key]) || $check)
                {
                    $fields[$key]["value"] = $_POST[$key];
                }
                else
                {
                    throw new Exception($key." is not filled properly");
                }
            }
            else
            {
                throw new Exception($key." is not filled");
            }
        }
    }
    catch(Exception $e)
    {
        create_message([['title' => 'Error !', 'content' => $e->getMessage(), 'color' => 'error', 'delete' => true, 'container' => true]]);
        header('Location: index.php');
        die();
    }

    try
    {
        $BDD->patch_toornament($fields["client_id"]["value"], $fields["client_secret"]["value"], $fields["api_key"]["value"], $fields["toornament_id"]["value"], $fields["webhook_name"]["value"], $fields["webhook_url"]["value"]);
        $TOORNAMENT->setToornamentConfiguration($fields["client_id"]["value"], $fields["client_secret"]["value"], $fields["api_key"]["value"], $fields["toornament_id"]["value"]);
    }
    catch(Exception $e)
    {
        create_message([['title' => 'Error !', 'content' => $e->getMessage(), 'color' => 'error', 'delete' => true, 'container' => true]]);
        header('Location: index.php');
        die();
    }

    try
    {
        $toornamentWebhook = $TOORNAMENT->get_webhook();
        $webhook_exist = false;
        if(count($toornamentWebhook["body"]) > 0)
        {
            foreach($toornamentWebhook["body"] as &$webhook)
            {
                if($webhook->name === $fields["webhook_name"]["value"])
                {
                    $webhook_exist = true;
                    $webhook_id = $webhook->id;
                    if($webhook->url !== $fields["webhook_url"]["value"])
                    {
                        $webhookUpdated = $TOORNAMENT->patch_webhook($webhook->id, $fields["webhook_name"]["value"], $fields["webhook_url"]["value"]);
                        $webhook_id = $webhookUpdated->id;
                    }
                }
            }
            if($webhook_exist === false)
            {
                $webhookCreated = $TOORNAMENT->post_webhook($fields["webhook_name"]["value"], $fields["webhook_url"]["value"]);
                $webhook_id = $webhookCreated["body"]->id;
            }
        }
        else
        {
            $webhookCreated = $TOORNAMENT->post_webhook($fields["webhook_name"]["value"], $fields["webhook_url"]["value"]);
            $webhook_id = $webhookCreated["body"]->id;
        }
    }
    catch(Exception $e)
    {
        create_message([['title' => 'Error !', 'content' => $e->getMessage()." Webhook failed", 'color' => 'error', 'delete' => true, 'container' => true]]);
        header('Location: index.php');
        die();
    }

    try
    {
        $toornamentSubscription = $TOORNAMENT->get_subscription($webhook_id);
        if(count($toornamentSubscription["body"]) > 0)
        {
            $subscription_done["registration.created"] = false;
            $subscription_done["registration.info_updated"] = false;
            foreach($toornamentSubscription["body"] as &$subscription)
            {
                if($subscription->scope_id === $TOORNAMENT->getToornamentId())
                {
                    $subscription_done[$subscription->event_name] = true;
                }
                else
                {
                    $TOORNAMENT->delete_subcription($webhook_id, $subscription->id);
                }
            }
            if($subscription_done["registration.created"] === false)
            {
                $TOORNAMENT->post_subscription($webhook_id, "registration.created", "tournament");
            }
            if($subscription_done["registration.info_updated"] === false)
            {
                $TOORNAMENT->post_subscription($webhook_id, "registration.info_updated", "tournament");
            }
        }
        else
        {
            $TOORNAMENT->post_subscription($webhook_id, "registration.created", "tournament");
            $TOORNAMENT->post_subscription($webhook_id, "registration.info_updated", "tournament");
        }
    }
    catch(Exception $e)
    {
        create_message([['title' => 'Error !', 'content' => $e->getMessage()." Subscription failed", 'color' => 'error', 'delete' => true, 'container' => true]]);
        header('Location: index.php');
        die();
    }

    create_message([['title' => 'Success !', 'content' => 'API configuration saved and webhook, subscription created', 'color' => 'success', 'delete' => true, 'container' => true]]);
    header('Location: index.php');
    die();
}

/**
 * @param Bdd $BDD
 */
function save_requirement($BDD)
{
    if(!check_csrf('csrf_requirement'))
    {
        create_message([['title' => 'Error !', 'content' => 'An error occurred when processing your request', 'color' => 'error', 'delete' => true, 'container' => true]]);
        header('Location: index.php');
        die();
    }

    $fields = [
        "age" => [
            "age_value" => ["type" => "numeric"],
            "age_match" => ["type" => "numeric"],
            "age_customField" => ["type" => "string"]
        ],
        "country" => [
            "country_value" => ["type" => "array"],
            "country_match" => ["type" => "numeric"],
            "country_customField" => ["type" => "string"]
        ]
    ];

    try
    {
        foreach($fields as $key => $value)
        {
            foreach($fields[$key] as $subKey => $subValue)
            {
                if(isset($_POST[$subKey]))
                {
                    if(!empty($_POST[$subKey]))
                    {
                        switch($fields[$key][$subKey]["type"])
                        {
                            case "alphanumeric":
                                $check = ctype_alnum($_POST[$subKey]);
                                break;
                            case "numeric":
                                $check = ctype_digit($_POST[$subKey]);
                                break;
                            case "string":
                                $check = preg_match_all("/^[0-9a-zA-Z _()]*$/", $_POST[$subKey]);
                                break;
                            case "array":
                                if(strpos($_POST[$subKey], ',') === false)
                                {
                                    $check = ctype_alnum($_POST[$subKey]);
                                }
                                else
                                {
                                    $split = str_replace(' ', '', explode(",", $_POST[$subKey]));
                                    $check = true;
                                    for($i=0; $i<count($split);$i++)
                                    {
                                        if(!ctype_alnum($split[$i]))
                                        {
                                            $check = false;
                                        }
                                    }
                                }
                                break;
                            default:
                                throw new Exception("An error occurred when processing your request");
                                break;
                        }
                    }
                    if(empty($_POST[$subKey]) || $check)
                    {
                        $fields[$key][$subKey]["value"] = $_POST[$subKey];
                    }
                    else
                    {
                        throw new Exception($key." is not filled properly");
                    }
                }
                else
                {
                    throw new Exception($subKey . " is not filled");
                }
            }
        }
        if(!empty($fields["age"]["age_customField"]["value"]) && !empty($fields["country"]["country_customField"]["value"]))
        {
            if($fields["age"]["age_customField"]["value"] === $fields["country"]["country_customField"]["value"])
            {
                throw new Exception("age custom field and country custom field must be different");
            }
        }

        if(isset($_POST["match"]) && !empty($_POST["match"]))
        {
            if($_POST["match"] !== "Ignore" || $_POST["match"] !== "Accept")
            {
                $match = $_POST["match"];
            }
            else
            {
                throw new Exception("match is not filled properly");
            }
        }
        else
        {
            throw new Exception("match is not filled");
        }
        if(isset($_POST["no_match"]) && !empty($_POST["no_match"]))
        {
            if($_POST["no_match"] !== "Ignore" || $_POST["no_match"] !== "Refuse")
            {
                $no_match = $_POST["no_match"];
            }
            else
            {
                throw new Exception("no_match is not filled properly");
            }
        }
        else
        {
            throw new Exception("no_match is not filled");
        }
    }
    catch(Exception $e)
    {
        create_message([['title' => 'Error !', 'content' => 'An error occurred when processing your request', 'color' => 'error', 'delete' => true, 'container' => true]]);
        header('Location: index.php');
        die();
    }

    try
    {
        foreach($fields as $key => $value)
        {
            $BDD->patch_requirement($key, $fields[$key][$key.'_value']["value"], $fields[$key][$key.'_match']["value"], $fields[$key][$key.'_customField']["value"], $fields[$key][$key.'_value']["type"]);
        }

        $BDD->patch_setting("match", $match);
        $BDD->patch_setting("no_match", $no_match);
    }
    catch(Exception $e)
    {
        create_message([['title' => 'Error !', 'content' => 'An error occurred when processing your request', 'color' => 'error', 'delete' => true, 'container' => true]]);
        header('Location: index.php');
        die();
    }

    create_message([['title' => 'Success !', 'content' => 'Requirements configuration saved', 'color' => 'success', 'delete' => true, 'container' => true]]);
    header('Location: index.php');
    die();
}

/**
 * @param Bdd $BDD
 * @param Toornament $TOORNAMENT
 */
function purge_webhook($BDD, $TOORNAMENT)
{
    try
    {
        $toornamentWebhook = $TOORNAMENT->get_webhook();
        $apiConfiguration = $BDD->get_toornament();
        if(count($toornamentWebhook["body"]) > 0)
        {
            foreach($toornamentWebhook["body"] as &$webhook)
            {
                if($webhook->name === $apiConfiguration["webhook_name"])
                {
                    $TOORNAMENT->delete_webhook($webhook->id);
                    $BDD->delete_webhook_secret();
                    create_message([['title' => 'Success !', 'content' => "Webhook ".$apiConfiguration["webhook_name"]." purged", 'color' => 'success', 'delete' => true, 'container' => true]]);
                    header('Location: index.php');
                    die();
                }
            }
            create_message([['title' => 'Success !', 'content' => "None ".$apiConfiguration["webhook_name"]." Webhook to purge", 'color' => 'success', 'delete' => true, 'container' => true]]);
            header('Location: index.php');
            die();
        }
        else
        {
            create_message([['title' => 'Success !', 'content' => "None ".$apiConfiguration["webhook_name"]." Webhook to purge", 'color' => 'success', 'delete' => true, 'container' => true]]);
            header('Location: index.php');
            die();
        }
    }
    catch(Exception $e)
    {
        create_message([['title' => 'Error !', 'content' => $e->getMessage()." Webhook failed to delete", 'color' => 'error', 'delete' => true, 'container' => true]]);
        header('Location: index.php');
        die();
    }
}