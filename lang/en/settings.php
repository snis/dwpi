<?php
/**
 * English language file for the config of the phpipam plugin
 * https://www.dokuwiki.org/devel:configuration#label_in_configuration_manager
 * https://phpipam.net/api/phpipam-api-clients/
 *
 * note: I had a blank page in the "Configuration Settings/Manager"
 *       i.e. ?do=admin&page=config
 *       After some tries, the problem seems to be the use of many "_"
 *       i.e. xml_tag_o_ul xml_tag_o_tr xml_tag_dl xml_dir_o_en
 *       Same problem with the use of "-" as seen in another plugin.
 *       i.e. xml_tag_o_ul xml_tag_o_tr xml_tag_dl xml_dir_o_en
 *       Finally, the culpirits appear to be options localised strings!
 *       https://www.dokuwiki.org/devel:configuration#parameters
 */

$phpipam = "<i>{php}IPAM</i>" ;

// PHPIPAM REST API
$not_enc = "<br /><small>Not used for encrypted requests.</small>" ;
$lang['api_url']       = "URL of $phpipam API server.<br /><small>E.g. <samp>http://somehost/phpipam/api/</samp></small>" ;
$lang['api_app']       = "Application Identifier for $phpipam API.<br /><small>This is set under Administration / API</small>" ;
$lang['api_key']       = "Application Code for $phpipam API<br /><small>This is generated when security is set to <q>crypt</q>: this will be used to encrypt requests (leave blank when security is set to something else &mdash;<q>ssl</q> or <q>none</q>)</small>" ;
$lang['api_usr']       = "Username of some $phpipam account to use.<br /><small>This is set under Administration / Users</small>$not_enc" ;
$lang['api_pwd']       = "Password of the $phpipam account to use.$not_enc" ;
$lang['api_res']       = "Result format type for raw display <br />xml = eXtensible Markup Language <br />json = Javascript Structured Object Notation <br />array = PHP native array <br />object = PHP native object" ;
$lang['api_taf']       = "Save access token in file, otherwise request it before each effective query.$not_enc" ;

// Output Presentation Options : PHPIPAM
$no_nest = "<br /><small>From v1.3, be sure the API don't nest custom fields.</small>" ;
$c_field = "Custom field holdaing link on " ;
$e_field = "Coma separed extra fields to show for ";
$lang['opo_cf1']       = "$c_field addresses$no_nest" ;
$lang['opo_cf2']       = "$c_field hostnames$no_nest" ;
$lang['opo_cfd']       = "$c_field devices$no_nest" ;
$lang['opo_efa']       = "$e_field addresses$no_nest" ;
$lang['opo_efd']       = "$e_field devices$no_nest" ;
$lang['opo_efs']       = "$e_field networks$no_nest" ;
$lang['opo_efl']       = "$e_field locations$no_nest" ;
$lang['opo_efv']       = "$e_field VLANs$no_nest" ;
$lang['opo_efr']       = "$e_field VRFs$no_nest" ;
$lang['opo_ef2']       = "$e_field l2 domains$no_nest" ;
$lang['opo_l10']       = "Output (labels/headers) language" ;
$lang['opo_l10_o_en']  = "English" ;
$lang['opo_l10_o_fr']  = "Fran&ccedil;ais" ;
$lang['opo_mdn']       = "Main domain name.<br /><small>(leave blank if you don't want to strip it from hostnames)</small>" ;
$lang['opo_url']       = "URL of $phpipam web base.<br /><small>E.g. <samp>http://somehost/phpipam/</samp><br />(leave blank if you don't want to link to subnets page)</small>" ;

// Output Presentation Options : XHTML+CSS
$lang['opo_dat']       = "Show objects title" ; //= Description field As Title
$lang['opo_dsc']       = "Display type for objects presentation" ; //= DeSCription
$lang['opo_dsc_o_ul']  = "bullet list" ; # li ul ol enum item list listo liste items itemise itemize enumerate enumeration
$lang['opo_dsc_o_tr']  = "grid table" ; # td th tr grid array table grille tabelo tableau
$lang['opo_dsc_o_dl']  = "definition list" ; # dd dt dl vortaro definition dictionary dictionnaire
$lang['opo_dsc_o_pre'] = "no formatting" ; # pre raw txt asis code flat text as-is texte texto noformat no-format unformated non-formate preformated sans-format
$lang['opo_dsc_o_p']   = "paragraphs..." ; # 
$lang['opo_eca']       = "Automatically add colors<br /><small>(avoid if you want to use a custom CSS)</small>" ; //= Extra Colors Auto
$lang['opo_lst']       = "Display type for addresses/networks list" ; //= LiSTs
$lang['opo_lst_o_ul']  = "bullet list" ; # li ul ol enum item list listo liste items itemise itemize enumerate enumeration
$lang['opo_lst_o_tr']  = "grid table" ; # td th tr grid array table grille tabelo tableau
$lang['opo_lst_o_dl']  = "definition list" ; # dd dt dl vortaro definition dictionary dictionnaire
$lang['opo_lst_o_p']   = "paragraphs..." ; # 

// ex: se ai et ts=4 st=4 bf :
// vi: se ai et ts=4 st=4 bf :
// vim: set ai et ts=4 st=4 bf sts=4 cin ff=unix fenc=utf-8 : enc=utf-8
// atom:set useSoftTabs tabLength=4 lineending=lf encoding=utf-8
// -*- Mode: tab-width: 4; c-basic-offset: 4; indent-tabs-mode: nil -*-
?>
