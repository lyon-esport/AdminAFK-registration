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

use DateTime, DateTimeZone, DateInterval;

class ManageRegistration
{
    private $toornament;
    private $requirements;
    private $settings;

    public function __construct($toornament = array(), $requirements = array(), $settings = array())
    {
        $this->toornament = $toornament;
        $this->requirements = $requirements;
        $this->settings = $settings;
    }

    public function status_registration($registration)
    {
        $match = $this->settings[0]["value"] === "Accept" ? "accepted" : "ignored";
        $no_match = $this->settings[1]["value"] === "Refuse" ? "refused" : "ignored";

        $check["age"] = (!empty($this->requirements[0]["value"]) && !empty($this->requirements[0]["match"]) && !empty($this->requirements[0]["custom_field"])) ? true : false;
        $check["country"] = (!empty($this->requirements[1]["value"]) && !empty($this->requirements[1]["match"]) && !empty($this->requirements[1]["custom_field"])) ? true : false;

        $age_match = ["number" => 0, "custom_field" => substr(explode(' ', $this->requirements[0]["custom_field"])[1], 1, -1), "status" => false];
        $country_match = ["number" => 0, "custom_field" => substr(explode(' ', $this->requirements[1]["custom_field"])[1], 1, -1), "status" => false];

        $all_check = true;

        if($check["age"])
        {
            if ($age_match["custom_field"] === "team" || $age_match["custom_field"] === "player")
            {
                $age_match["number"] += $this->match_age($registration, -1);
                $age_match["status"] = (intval($age_match["number"]) >= intval($this->requirements[0]["match"])) ? true : false;
            }
            else if ($age_match["custom_field"] === "team_player")
            {
                for ($i = 0; $i < count($registration->lineup); $i++)
                {
                    $age_match["number"] += $this->match_age($registration, $i);
                    $age_match["status"] = (intval($age_match["number"]) >= intval($this->requirements[0]["match"])) ? true : false;
                }
            }
            else
            {
                throw new Exception("custom_field type not found");
            }
        }
        if($check["country"])
        {
            if ($country_match["custom_field"] === "team" || $country_match["custom_field"] === "player")
            {
                $country_match["number"] += $this->match_country($registration, -1);
                $country_match["status"] = (intval($country_match["number"]) >= intval($this->requirements[1]["match"])) ? true : false;
            }
            else if ($country_match["custom_field"] === "team_player")
            {
                for ($i = 0; $i < count($registration->lineup); $i++)
                {
                    $country_match["number"] += $this->match_country($registration, $i);
                    $country_match["status"] = (intval($country_match["number"]) >= intval($this->requirements[1]["match"])) ? true : false;
                }
            }
            else
            {
                throw new Exception("custom_field type not found");
            }
        }

        if($all_check)
        {
            $all_check = $check["age"] ? $age_match["status"] : true;
        }

        if($all_check)
        {
            $all_check = $check["country"] ? $country_match["status"] : true;
        }

        return $all_check ? $match : $no_match;
    }

    private function match_age($registration, $key = -1)
    {
        $tz = new DateTimeZone('Europe/Paris');

        $requirement = [
            "value" => $this->requirements[0]["value"],
            "custom_field" => [
                "type" => substr(explode(' ', $this->requirements[0]["custom_field"])[1], 1, -1),
                "value" => explode(' ', $this->requirements[0]["custom_field"])[0]
            ]
        ];

        $field = $requirement["custom_field"]["value"];

        if($requirement["custom_field"]["type"] === "player" || $requirement["custom_field"]["type"] === "team")
        {
            if(intval(DateTime::createFromFormat('Y-m-d', $registration->custom_fields->$field, $tz)->diff((new DateTime($this->toornament->scheduled_date_start, $tz))->add(new DateInterval('P1D')))->y) >= intval($requirement["value"]))
            {
                return 1;
            }
        }
        elseif($requirement["custom_field"]["type"] === "team_player")
        {
                if(intval(DateTime::createFromFormat('Y-m-d', $registration->lineup[$key]->custom_fields->$field, $tz)->diff((new DateTime($this->toornament->scheduled_date_start, $tz))->add(new DateInterval('P1D')))->y) >= intval($requirement["value"]))
                {
                    return 1;
                }
        }

        return 0;
    }

    private function match_country($registration, $key = -1)
    {
        $requirement = [
            "value" => array_map('strtolower', str_replace(' ', '', explode(",", $this->requirements[1]["value"]))),
            "custom_field" => [
                "type" => substr(explode(' ', $this->requirements[1]["custom_field"])[1], 1, -1),
                "value" => explode(' ', $this->requirements[1]["custom_field"])[0]
            ]
        ];

        $field = $requirement["custom_field"]["value"];

        if($requirement["custom_field"]["type"] === "player" || $requirement["custom_field"]["type"] === "team")
        {
            if(in_array(strtolower($registration->custom_fields->$field), $requirement["value"]))
            {
                return 1;
            }
        }
        elseif($requirement["custom_field"]["type"] === "team_player")
        {
            if(in_array(strtolower($registration->lineup[$key]->custom_fields->$field), $requirement["value"]))
            {
                return 1;
            }
        }

        return 0;
    }
}