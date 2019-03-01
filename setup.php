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

if(file_exists ('database.sqlite'))
{
    echo "Database already exist !<br> <a href='index.php'>Go back to homepage</a>";
}
else
{
    try
    {
        $BDD = new PDO('sqlite:database.sqlite');
        $BDD->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $BDD->prepare('
                                        CREATE TABLE IF NOT EXISTS toornament ( 
                                        id                INTEGER         PRIMARY KEY AUTOINCREMENT,
                                        api_key     VARCHAR( 255 ),
                                        client_id    VARCHAR( 255 ),
                                        client_secret  VARCHAR( 255 ),
                                        toornament_id     VARCHAR( 255 ),
                                        webhook_name     VARCHAR( 255 ),
                                        webhook_url     VARCHAR( 255 ),
                                        webhook_secret     VARCHAR( 255 )
                                        );');
        $stmt->execute();
        $stmt->closeCursor();

        $stmt = $BDD->prepare('
                                        INSERT INTO toornament
                                        (api_key, client_id, client_secret, toornament_id, webhook_url, webhook_name, webhook_secret)
                                        VALUES
                                        (:api_key, :client_id, :client_secret, :toornament_id, :webhook_name, :webhook_url, :webhook_secret)');
        $stmt->execute(array(":api_key" => "", ":client_id" => "", ":client_secret" => "", ":toornament_id" => "", ":webhook_name" => "", ":webhook_url" => "", ":webhook_secret" => ""));
        $stmt->closeCursor();

        $stmt = $BDD->prepare('
                                        CREATE TABLE IF NOT EXISTS requirement ( 
                                        id                INTEGER         PRIMARY KEY AUTOINCREMENT,
                                        name     VARCHAR( 255 ),
                                        value     VARCHAR( 1000 ),
                                        match    VARCHAR( 255 ),
                                        custom_field  VARCHAR( 255 ),
                                        type     VARCHAR( 255 ),
                                        UNIQUE(name)
                                        );');
        $stmt->execute();
        $stmt->closeCursor();

        $stmt = $BDD->prepare('
                                        INSERT INTO requirement
                                        (name, value, match, custom_field, type)
                                        VALUES
                                        (:name, :value, :match, :custom_field, :type)');
        $stmt->execute(array(":name" => "age", ":value" => "", ":match" => "", ":custom_field" => "", ":type" => "Integer"));
        $stmt->closeCursor();
        $stmt->execute(array(":name" => "country", ":value" => "", ":match" => "", ":custom_field" => "", ":type" => "String"));
        $stmt->closeCursor();

        $stmt = $BDD->prepare('
                                        CREATE TABLE IF NOT EXISTS setting ( 
                                        id                INTEGER         PRIMARY KEY AUTOINCREMENT,
                                        name     VARCHAR( 255 ),
                                        value    VARCHAR( 255 ),
                                        UNIQUE(name)
                                        );');
        $stmt->execute();
        $stmt->closeCursor();

        $stmt = $BDD->prepare('
                                        INSERT INTO setting
                                        (name, value)
                                        VALUES
                                        (:name, :value)');
        $stmt->execute(array(":name" => "match", ":value" => "Accept"));
        $stmt->execute(array(":name" => "no_match", ":value" => "Refuse"));
        $stmt->closeCursor();

        echo "Database created !<br> <a href='index.php'>Go back to homepage</a>";
    }
    catch (Exception $e)
    {
        echo "Error : " . $e->getMessage();
        die();
    }
}