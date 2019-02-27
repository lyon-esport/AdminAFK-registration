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

use Exception;


class Toornament
{
    private $api_key;
    private $client_id;
    private $client_secret;
    private $toornament_id;
    private $webhook_url;
    private $webhook_secret;

    public function __construct($toornamentInfo)
    {
        $this->api_key = $toornamentInfo["api_key"];
        $this->client_id = $toornamentInfo["client_id"];
        $this->client_secret = $toornamentInfo["client_secret"];
        $this->toornament_id = $toornamentInfo["toornament_id"];
        $this->webhook_url = $toornamentInfo["webhook_url"];
        $this->webhook_secret = $toornamentInfo["webhook_secret"];
    }

    public function post_webhook()
    {
        $body = '{"enabled": true, "name": "AdminAFK-registration", "url": "'.$this->webhook_url.'"}';
        return $this->POST('organizer/v2/webhooks', $this->get_token("organizer:registration")["body"]->access_token, $body);
    }

    public function post_subscription($webhook_id = "", $event_name = "", $scope = "")
    {
        $body = '{"event_name": "'.$event_name.'", "scope": "'.$scope.'", "scope_id": "'.$this->toornament_id.'"}';
        return $this->POST('organizer/v2/webhooks/'.$webhook_id.'/subscriptions', $this->get_token("organizer:registration")["body"]->access_token, $body);
    }

    public function patch_webhook($webhook_id)
    {
        $body = '{"enabled": true, "name": "AdminAFK-registration", "url": "'.$this->webhook_url.'"}';
        return $this->PATCH('organizer/v2/webhooks/'.$webhook_id, $this->get_token("organizer:registration")["body"]->access_token, $body);
    }

    public function patch_subscription($webhook_id = "", $event_name = "", $scope = "")
    {
        $body = '{"event_name": "'.$event_name.'", "scope": "'.$scope.'", "scope_id": "'.$this->toornament_id.'"}';
        return $this->PATCH('organizer/v2/webhooks/'.$webhook_id.'/subscriptions', $this->get_token("organizer:registration")["body"]->access_token, $body);
    }

    public function patch_registration($registration_id = "", $status = "")
    {
        $body = '{"status": "'.$status.'"}';
        return $this->PATCH('organizer/v2/tournaments/'.$this->toornament_id.'/registrations/'.$registration_id, $this->get_token("organizer:registration")["body"]->access_token, $body);
    }

    public function delete_webhook($webhook_id)
    {
        return $this->DELETE('organizer/v2/webhooks/'.$webhook_id, $this->get_token("organizer:registration")["body"]->access_token);
    }

    public function get_webhook()
    {
        $range_start = 0;
        $range_stop = 49;
        $webhooks = ["http_code" => 0, "body" => []];
        $request["http_code"] = 206;
        $request["body"] = array_fill(0, 50, '');

        while($request["http_code"] === 206 && count($request["body"]) === 50)
        {
            $request = $this->GET('organizer/v2/webhooks', $this->get_token("organizer:registration")["body"]->access_token, 'webhooks='.$range_start.'-'.$range_stop);
            $webhooks["body"] = array_merge($webhooks["body"], $request["body"]);
            $range_start += 50;
            $range_stop += 50;
        }
        $webhooks["http_code"] = 200;
        return $webhooks;
    }

    public function get_subscription($webhook_id = "")
    {
        $range_start = 0;
        $range_stop = 49;
        $webhooks = ["http_code" => 0, "body" => []];
        $request["http_code"] = 206;
        $request["body"] = array_fill(0, 50, '');

        while($request["http_code"] === 206 && count($request["body"]) === 50)
        {
            $request = $this->GET('organizer/v2/webhooks/'.$webhook_id.'/subscriptions', $this->get_token("organizer:registration")["body"]->access_token, 'subscriptions='.$range_start.'-'.$range_stop);
            $webhooks["body"] = array_merge($webhooks["body"], $request["body"]);
            $range_start += 50;
            $range_stop += 50;
        }
        $webhooks["http_code"] = 200;
        return $webhooks;
    }

    public function get_custom_fields()
    {
        return $this->GET('organizer/v2/tournaments/'.$this->toornament_id.'/custom-fields', $this->get_token("organizer:admin")["body"]->access_token, '');
    }

    public function get_registration()
    {
        $range_start = 0;
        $range_stop = 49;
        $registrations = ["http_code" => 0, "body" => []];
        $request["http_code"] = 206;
        $request["body"] = array_fill(0, 50, '');

        while($request["http_code"] === 206 && count($request["body"]) === 50)
        {
            $request = $this->GET('organizer/v2/tournaments/'.$this->toornament_id.'/registrations', $this->get_token("organizer:registration")["body"]->access_token, 'registrations='.$range_start.'-'.$range_stop);
            $registrations["body"] = array_merge($registrations["body"], $request["body"]);
            $range_start += 50;
            $range_stop += 50;
        }
        $registrations["http_code"] = 200;
        return $registrations;
    }

    public function get_registrationById($registration_id = "")
    {
        return $this->GET('organizer/v2/tournaments/'.$this->toornament_id.'/registrations/'.$registration_id, $this->get_token("organizer:registration")["body"]->access_token, '');
    }

    public function get_tournament_info()
    {
        return $this->GET('organizer/v2/tournaments/'.$this->toornament_id, $this->get_token("organizer:view")["body"]->access_token, '');
    }

    public function get_token($token_scope)
    {
        return $this->GET('oauth/v2/token?grant_type=client_credentials&client_id='.$this->client_id.'&client_secret='.$this->client_secret.'&scope='.$token_scope, '', '');
    }

    public function GET($endpoint = "", $token = "", $endpoint_option = "")
    {
        $curl = curl_init();
        $option = array(
            CURLOPT_URL             => 'https://api.toornament.com/'.$endpoint,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_VERBOSE         => true,
            CURLOPT_HEADER          => true,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPHEADER      => array(
                'X-Api-Key: '.$this->api_key,
                'X-Webhook-Secret: '.$this->api_key,
                'Content-Type: application/json'
            )
        );
        $option[10023][2] = ($token !== "" ? 'Authorization: Bearer '.$token : "");
        $option[10023][3] = ($endpoint_option !== "" ? 'Range: '.$endpoint_option : "");

        curl_setopt_array($curl, $option);
        $output         = curl_exec($curl);
        $header_size    = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $body           = json_decode(substr($output, $header_size));
        $httpcode 		= curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if($httpcode != 200 && $httpcode != 201 && $httpcode != 204 && $httpcode != 206)
        {
            throw new Exception($httpcode);
        }
        return array("body" => $body, "http_code" => $httpcode);
    }

    public function POST($endpoint = "", $token = "", $request_body = array())
    {
        $curl = curl_init();
        $option = array(
            CURLOPT_URL             => 'https://api.toornament.com/'.$endpoint,
            CURLOPT_POST            => 1,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_VERBOSE         => true,
            CURLOPT_HEADER          => true,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPHEADER      => array(
                'X-Api-Key: '.$this->api_key,
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS      => $request_body
        );
        $option[10023][2] = ($token !== "" ? 'Authorization: Bearer '.$token : "");

        curl_setopt_array($curl, $option);
        $output         = curl_exec($curl);
        $header_size    = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $body           = json_decode(substr($output, $header_size));
        $httpcode 		= curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if($httpcode != 200 && $httpcode != 201 && $httpcode != 204 && $httpcode != 206)
        {
            throw new Exception($httpcode);
        }
        return array("body" => $body, "http_code" => $httpcode);
    }

    public function PATCH($endpoint = "", $token = "", $request_body = "")
    {
        $curl = curl_init();
        $option = array(
            CURLOPT_URL             => 'https://api.toornament.com/'.$endpoint,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_VERBOSE         => true,
            CURLOPT_HEADER          => true,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPHEADER      => array(
                'X-Api-Key: '.$this->api_key,
                'Content-Type: application/json'
            ),
            CURLOPT_CUSTOMREQUEST   => "PATCH",
            CURLOPT_POSTFIELDS      => $request_body
        );
        $option[10023][2] = ($token !== "" ? 'Authorization: Bearer '.$token : "");

        curl_setopt_array($curl, $option);
        $output         = curl_exec($curl);
        $header_size    = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $body           = json_decode(substr($output, $header_size));
        $httpcode 		= curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if($httpcode != 200 && $httpcode != 201 && $httpcode != 204 && $httpcode != 206)
        {
            throw new Exception($httpcode);
        }
        return array("body" => $body, "http_code" => $httpcode);
    }

    public function DELETE($endpoint = "", $token = "")
    {
        $curl = curl_init();
        $option = array(
            CURLOPT_URL             => 'https://api.toornament.com/'.$endpoint,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_VERBOSE         => true,
            CURLOPT_HEADER          => true,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPHEADER      => array(
                'X-Api-Key: '.$this->api_key,
                'Content-Type: application/json'
            ),
            CURLOPT_CUSTOMREQUEST   => "DELETE"
        );
        $option[10023][2] = ($token !== "" ? 'Authorization: Bearer '.$token : "");

        curl_setopt_array($curl, $option);
        $output         = curl_exec($curl);
        $header_size    = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $body           = json_decode(substr($output, $header_size));
        $httpcode 		= curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if($httpcode != 200 && $httpcode != 201 && $httpcode != 204 && $httpcode != 206)
        {
            throw new Exception($httpcode);
        }
        return array("body" => $body, "http_code" => $httpcode);
    }

    public function setToornamentId($toornament_id)
    {
        $this->toornament_id = $toornament_id;
    }

    public function setWebhookUrl($webhook_url)
    {
        $this->webhook_url = $webhook_url;
    }

    public function setWebhookSecret($webhook_secret)
    {
        $this->webhook_secret = $webhook_secret;
    }

    public function getApiKey()
    {
        return $this->api_key;
    }

    public function getClientId()
    {
        return $this->client_id;
    }

    public function getClientSecret()
    {
        return $this->client_secret;
    }

    public function getToornamentId()
    {
        return $this->toornament_id;
    }

    public function getWebhookUrl()
    {
        return $this->webhook_url;
    }

    public function getWebhookSecret()
    {
        return $this->webhook_secret;
    }

}