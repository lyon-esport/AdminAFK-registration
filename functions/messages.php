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

function create_message($array_message)
{
    $color = [
        'error' => 'is-danger',
        'warning' => 'is-warning',
        'success' => 'is-success',
        'info' => 'is-info'
    ];
    for($i=0;$i<count($array_message);$i++)
    {
        $_SESSION['message_'.$i]['title'] = $array_message[$i]['title'];
        $_SESSION['message_'.$i]['content'] = $array_message[$i]['content'];
        $_SESSION['message_'.$i]['color'] = $color[$array_message[$i]['color']];
        $_SESSION['message_'.$i]['delete'] = (bool) $array_message[$i]['delete'];
        $_SESSION['message_'.$i]['container'] = (bool) $array_message[$i]['container'];
    }
    $_SESSION['nbMessage'] = count($array_message);
}

function get_message()
{
    $message = [];
    if(isset($_SESSION['nbMessage']) && !empty($_SESSION['nbMessage']))
    {
        for($i=0;$i<$_SESSION['nbMessage'];$i++)
        {
            if(isset($_SESSION['message_'.$i]['title'])){$message['message_'.$i]['title'] = $_SESSION['message_'.$i]['title'];}
            if(isset($_SESSION['message_'.$i]['content'])){$message['message_'.$i]['content'] = $_SESSION['message_'.$i]['content'];}
            if(isset($_SESSION['message_'.$i]['color'])){$message['message_'.$i]['color'] = $_SESSION['message_'.$i]['color'];}
            if(isset($_SESSION['message_'.$i]['delete'])){$message['message_'.$i]['delete'] = $_SESSION['message_'.$i]['delete'];}
            if(isset($_SESSION['message_'.$i]['container'])){$message['message_'.$i]['container'] = $_SESSION['message_'.$i]['container'];}

            unset($_SESSION['message_'.$i]['title']);
            unset($_SESSION['message_'.$i]['content']);
            unset($_SESSION['message_'.$i]['color']);
            unset($_SESSION['message_'.$i]['delete']);
            unset($_SESSION['message_'.$i]['container']);
        }
        unset($_SESSION['nbMessage']);
    }
    return $message;
}