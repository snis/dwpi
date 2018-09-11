<?php
/**
 * Configuration ptions for the phpipam plugin
 * https://www.dokuwiki.org/devel:configuration#configuration_metadata
 */

if ( !defined('DOKU_PLUGIN') )
    define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/') ;

// PHPIPAM REST API
$meta['api_url']       = array( 'string', '_pattern' => '#https?://\w+#', '_delimiter' => '#' ) ;
$meta['api_app']       = array( 'string', '_pattern' => '/\w+/' ) ;
$meta['api_key']       = array( 'string', '_pattern' => '/[a-f0-9]*/' ) ;
$meta['api_usr']       = array( 'string', '_pattern' => '/\w*/' ) ;
$meta['api_pwd']       = array( 'password', '_code' => 'plain' ) ;
$meta['api_taf']       = array( 'onoff' ) ;

// Output Presentation Options : PHPIPAM
$meta['opo_cf1']       = array( 'string', '_pattern' => '/\w*/' ) ;
$meta['opo_cf2']       = array( 'string', '_pattern' => '/\w*/' ) ;
$meta['opo_efa']       = array( 'string', '_pattern' => '/[\w,]*/' ) ;
$meta['opo_efs']       = array( 'string', '_pattern' => '/[\w,]*/' ) ;
$meta['opo_efd']       = array( 'string', '_pattern' => '/[\w,]*/' ) ;
$meta['opo_efl']       = array( 'string', '_pattern' => '/[\w,]*/' ) ;
$meta['opo_efv']       = array( 'string', '_pattern' => '/[\w,]*/' ) ;
$meta['opo_efr']       = array( 'string', '_pattern' => '/[\w,]*/' ) ;
#$meta['opo_ef2']       = array( 'string', '_pattern' => '/[\w,]*/' ) ;
$meta['opo_l10']       = array( 'dirchoice', '_dir' => DOKU_PLUGIN.'phpipam/lang/' ) ;
$meta['opo_mdn']       = array( 'string', '_pattern' => '/[\w\.]*/' ) ;
$meta['opo_url']       = array( 'string', '_pattern' => '#(https?://\w+)?#', '_delimiter' => '#' ) ;

// Output Presentation Options : XHTML+CSS
$meta['opo_dat']       = array( 'onoff' ) ;
$meta['opo_eca']       = array( 'onoff' ) ;
$meta['opo_dsc']       = array( 'multichoice', '_other' => 'exists', '_choices' => array('dl','tr','ul','p','pre') ) ;
$meta['opo_lst']       = array( 'multichoice', '_other' => 'exists', '_choices' => array('ul','tr','dl', 'p') ) ;
#$meta['opo_css']       = array( 'dirchoice', '_dir' => DOKU_PLUGIN.'phpipam/ui/' ) ;

// ex: se ai et ts=4 st=4 bf :
// vi: se ai et ts=4 st=4 bf :
// vim: set ai et ts=4 st=4 bf sts=4 cin ff=unix fenc=utf-8 : enc=utf-8
// atom:set useSoftTabs tabLength=4 lineending=lf encoding=utf-8
// -*- Mode: tab-width: 4; c-basic-offset: 4; indent-tabs-mode: nil -*-
?>
