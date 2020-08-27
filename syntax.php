<?php
/**
 * phpipam plugin (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC'))
    die() ;

if (!defined('DOKU_PLUGIN'))
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/') ;
require_once(DOKU_PLUGIN . 'syntax.php') ;

// include api client class file
/// @note https://github.com/phpipam/phpipam-api-clients/tree/master/php-client
require_once(dirname(__FILE__) . '/phpipam-api.php') ;

/**
 * our Syntax class
 *
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from the class Dokuwiki_Syntax_Plugin
 */
class syntax_plugin_phpipam extends Dokuwiki_Syntax_Plugin {

    /**
     * Return some info
     *
     * @note https://www.docuwiki.org/devel:plugin_info
     */
    /*
    function getInfo(){
        return array(
            'author' => 'Gildas Cotomale',
            'email'  => 'Gildas.Cotomale@ymagis.com',
            'date'   => '2020-08-27',
            'name'   => '{php}IPAM connector plugin',
            'desc'   => 'Displays a network used-or-reserved IPs',
            'url'    => 'http://www.dokuwiki.org/plugin:phpipam',
        ) ;
    }
     */

    /**
     * What kind of syntax are we?
     *
     * @note https://www.dokuwiki.org/devel:syntax_plugins#synopsis
     * @note https://www.dokuwiki.org/devel:syntax_plugins#mode_types
     * @note https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     */
    public function getType() {
        return 'container' ;
    }

    /**
     * What other syntax may be inside?
     *
     * @note https://www.dokuwiki.org/devel:syntax_plugins#synopsis
     * @note https://www.dokuwiki.org/devel:syntax_plugins#allowed_modes
     * @note https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     */
    public function getAllowedType() {
        return array(
            'formatting',
            'substition',
        ) ;
    }

    /**
     * Paragraph Type
     *
     * @note https://www.dokuwiki.org/devel:syntax_plugins#synopsis
     * @note https://www.dokuwiki.org/devel:syntax_plugins#ptype
     */
    public function getPType() {
        return 'block' ;
    }

    /**
     * Where to sort in?
     *
     * @note https://www.dokuwiki.org/devel:syntax_plugins#synopsis
     * @note https://www.dokuwiki.org/devel:syntax_plugins#sort_number
     * @note https://www.dokuwiki.org/devel:parser:getsort_list
     * @note https://www.dokuwiki.org/devel:parser#order_of_adding_modes_important
     */
    public function getSort() {
        return 102 ;
    }

    /**
     * Connect lookup pattern to Lexer
     *
     * @note https://www.dokuwiki.org/devel:syntax_plugins#synopsis
     * @note https://www.dokuwiki.org/devel:syntax_plugins#patterns
     * @note https://www.dokuwiki.org/devel:plugin_programming_tips#use_correct_regular_expressions
     * @note https://forum.dokuwiki.org/thread/15542 =>
     * @note https://www.dokuwiki.org/plugin?relativens#code
     */
    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\B<(?i)phpipam\s+?.+?\/>\E',
            $mode, 'plugin_phpipam') ;
        $this->Lexer->addSpecialPattern('\B@(?i)phpipam\s+?.+?\/@\E',
            $mode, 'plugin_phpipam') ;
        $this->Lexer->addSpecialPattern('\B@(?i)phpipam\s+?.+?@@\E',
            $mode, 'plugin_phpipam') ;
        $this->Lexer->addSpecialPattern('\B~(?)phpipam\s+?.+?\/~\E',
            $mode, 'plugin_phpipam') ;
        $this->Lexer->addSpecialPattern('\B~(?)phpipam\s+?.+?~~\E',
            $mode, 'plugin_phpipam') ;
    }

    /**
     * Handle the matches
     *
     * @note https://www.dokuwiki.org/devel:syntax_plugins#synopsis
     * @note https://www.dokuwiki.org/devel:syntax_plugins#handle_method
     */
    public function handle($match, $state, $pos, Doku_Handler $handler) {
        // disable syntax in user comments (discussion plugin)
        if (isset($_REQUEST['comment'])) {
            return false ;
        }
        // default options
        $opts = array(
            'cst'  => array(
                #'links' => 0,
            ),
        ) ;
        // parse attributes...
        $params = preg_split('/\\\\.(*SKIP)(*FAIL)|[\s,;\|&]+/',
            substr($match,9,-2), 0, PREG_SPLIT_NO_EMPTY) ;
        unset($match) ;
        foreach ($params as $param1) {
            $param1 = trim($param1) ;
            if (preg_match("/^(cidr|add?ress?e?s?|net\w+|[\w-]*nets?|range|[\w-]*re[dst]\w*|si[et]\w*|\w*v[oe]rk\w*|cet\w+)[=:%#@]['\"]?((25[0-5]|2[0-4]\d|[01]?\d\d?.){3})(25[0-5]|2[0-4]\d|[01]?\d\d?)\/(3[0-2]|[12]?\d)['\"]?$/i",
                $param1, $matches)) { // SubNet search
                #0:: add?ress?e?s?
                #0:: \w+nets?
                #0:> subnet/address (english), sous-reseau/adresse (french),
                #1:: net\w*
                #1:> net (icelandie),
                #1:> nettverk (norwegian),
                #1:> nettverk (norwegian),
                #1:> netvaerk (danish),
                #1:> network (dutch/english/maltese/),
                #1:> netzwerk (german),
                #2:: re[dst]\w*
                #2:> red (spanish),
                #2:> rede (galician/portuguese),
                #2:> reseau (french),
                #2:> rete (italian),
                #2:> retea (romanian),
                #2:> reto (esperanto),
                #3:: si[et]\w?
                #3:> siec (polish),
                #3:> siet (slovak),
                #3:> sit (czech),
                #4:: \w*v[eo]r\w+
                #4:> nettverk (norwegian),
                #4:> verkko (finnish),
                #4:> vork (estonian),
                #5:: cet\w+
                #5:> cetb (rusiaan),
                #5:> cetka (belarusian),
                #6:: tin?kla?s
                #6:> tikls (latvian),
                #6:> tinklas (lithuanian),
                #7:: me?pex[ay]
                #7:> mpexa (bulgarian/macedonian/serbian),
                #7:> mepexy (ukranian),
                #8:: o?mrezj?[ae]
                #8:> mreza (croatian),
                #8:> omrezje (slovenian),
                #9:: 
                #9:> halozat (hungarian),
                #9:> liora (irish),
                #9:> nat (swedish),
                #9:> rhwydwaith (welsh),
                #9:> rrjet (albanian),
                #9:> sarea (basque),
                #9:> xarxa (catalan),
                $opts['net'] = $matches[2] . $matches[4] . '/' .$matches[5] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(vlanid|vnetid|vpcid|virtid|vpcid|vid)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // Virtual LAN (i.e. routers l2 Network) index
                $opts['vid'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(vlan|vnet|vnet|vdom|virt|vpc)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // Virtual LAN (i.e. routers l2 Network) number
                $opts['vlan'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^([sn]?id|num[abemorsu]*|nombr[eo]|nm?br|subnetid|networkid)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // SubNet index
                #0> Subnet/Network (specific/numeric) IDentifier (interger)
                #1: num[abemorsu]+
                #1> numar (romanian),
                #1> numara (turkish),
                #1> number (english/estonian),
                #1> numer (albanian),
                #1> numero (finnish/galician/italian/portuguese/spanish),
                #1> nummer (danish/icelandic/norwegian/swedish),
                #1> numru (maltese),
                #1> numurs (latvian),
                #2: nombr[eo]
                #2> nombre (catalan/french),
                #2> nombro (esperanto),
                #2: 
                #3> 6poj (macedonian/serbian),
                #3> aantal (dutch),
                #3> anzahl (german),
                #3> broj (croatian),
                #3> cislo (czech/slovak),
                #3> liczba (polish),
                #3> rhif (welsh),
                #3> skaicius (lithuanian),
                #3> stevilo (slovenian),
                #3> szam (hungarian),
                #3> uimhir (irish),
                #3> zenbakia (basque),
                $opts['sid'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(did|deviceid)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // Device index
                $opts['did'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(eid|rackid|cabinetid)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // Rack index
                $opts['eid'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(tid|tagid|statusid)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // IP Status index
                $opts['tid'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(lid|locationid|sisiteid|placeid)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // Location index
                $opts['lid'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(loc|location|sisite|place|plc)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // Location index
                $opts['loc'] = $matches[2] ;
                #array_splice($params,0) ;
            /*
            } elseif (preg_match("/^(ns|nameserver|dns)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // Location index
                $opts['dns'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(zone.*|p[ao]rt|type|z[pb]f|zid)[=:%#@]['\"]?([\w-]+)['\"]?$/i", $param1, $matches)) {
                // Zone Policy Firewall (for Zone-Based Firewall)
                $opts['zid'] = $matches[2]; // zone+mapping and module since 1.20
                #array_splice($params,0) ;
            */
            } elseif (preg_match("/^['\"]?(\d+)['\"]?$/", $param1, $matches)) {
                $opts['sid'] = $matches[1] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(d2[sn])[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // Device to SubNet
                $opts['d2s'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(f2[sn])[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // Folder to SubNet
                $opts['f2s'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(l2[sn])[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // Location to SubNet
                $opts['l2s'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(r2[sn])[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // VRF to SubNet
                $opts['r2s'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(s2[sn])[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // Section to SubNet
                $opts['s2s'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(v2[sn])[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // VLAN to SubNet
                $opts['v2s'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(n2[sn])[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // VLAN# to SubNet
                $opts['n2s'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(t2d)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // DeviceType to Device
                $opts['t2d'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(l2d)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // Location to Device
                $opts['l2d'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(e2d)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // Rack to Device
                $opts['e2d'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(l2e)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // Location to Rack
                $opts['l2e'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(d2e)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // Device to Rack
                $opts['d2e'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(22v)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // l2 domain to VLAN
                $opts['22v'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(2id|domainid|l2domain)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // l2 domain
                $opts['2id'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(rid|vrfid|)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // VRF
                $opts['rid'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(vrfid|)[=:%#@]['\"]?(\d+)['\"]?$/i",
                $param1, $matches)) { // VRF
                $opts['vrf'] = $matches[2] ;
                #array_splice($params,0) ;
            } elseif (preg_match("/^(form\w*|fmt|disp\w*|affich\w*|out\w*|show|x?html?|xmltag)[=:%#@]['\"]?(\w+)['\"]?$/i",
                $param1, $matches)) { // part to show
                $opts['fmt'] = $matches[2] ;
                #array_splice($params,0) ;
            } else { // custom/other parameters
                if (preg_match("/^(\w+)[=:%#@]['\"]?((?:.(?![\"']?(?:\w+)[=:%#@]|[>\"']))+.?)['\"]?/",
                    $param1, $matches)) { // pair key=>value
                    $opts['cst'][$matches[1]] = $matches[2] ;
                    #array_splice($params,0) ;
                 }
            }
        }
        unset($params) ;
        // set output formating
        switch (strtolower($opts['fmt'])) {
            case 'base' :
            case 'desc' :
            case 'head' :
            case 'info' :
            case 'main' :
            case 'meta' :
            case 'front' : // for racks
            case 'infos' :
            case 'description' :
            case 'information' :
            case 'presentation' :
                $opts['fmt'] = '-1' ;
                break ;
            case 'ip' :
            case 'ips' :
            case 'back' : // for racks
            case 'list' :
            case 'rear' : // for racks
            case 'tail' :
            case 'used' :
            case 'hosts' : // for subnets/locations/devices
            case 'subnets' : // for VLANs/VRFs/l2domains/
            case 'networks' : // for VLANs/VRFs/l2domains/
            case 'addresses' : // for subnets/locations/devices
                $opts['fmt'] = '+1' ;
                break ;
            case 'all' :
            case 'any' :
            case 'both' :
                $opts['fmt'] = '0' ;
                break ;
            default:
                $opts['fmt'] = (int)$opts['fmt'] ;
                break ;
        }
        // pass them around now
        return $opts ;
    }

    /**
     * Create output
     *
     * @note https://www.dokuwiki.org/devel:syntax_plugins#synopsis
     * @note https://www.dokuwiki.org/devel:syntax_plugins#render_method
     */
    public function render($mode, Doku_Renderer $renderer, $opts) {
        if ($mode == 'xhtml' && is_array($opts) and $opts) {
            // init IPAM object with setting
            $rest_api = new phpipam_api_client(
                $this->getConf('api_url'), $this->getConf('api_app'),
                $this->getConf('api_key') ? $this->getConf('api_key') : false,
                $this->getConf('api_usr'), $this->getConf('api_pwd'), 'array') ;
            // token file
            $token_file = ($this->getConf('api_taf') ? 'token' : false) ;
            // fetch answers using:
            // $rest_api->execute($method, $controller, array($param1, $param2, ...), array($global1, $global2, ...) ;
            // that will generate request like
            // - 1.2 unencrypted/SSL:
            // $method $api-url/api/$api-name/$controller/$param1/$param2/...
            // - 1.3 encrypted:
            // GET $api-url/?app_id=$api-name&enc=$encrypted_request&global1&$global2&...
            if ($opts['sid']) { // Subnet index
                $rest_api->execute('GET', 'subnets',
                    array($opts['sid']), array(), $token_file) ;
                $reply = $rest_api->get_result() ;
                if ($reply['data']) {
                    $renderer->doc .= $this->showSub((array)$reply['data'], $opts['fmt']) ;
                } else {
                    $renderer->doc .= $this->showErr($reply, $opts) ;
                }
            } elseif ($opts['net']) { // Subnets search by CIDR
                $rest_api->execute('GET', 'subnets',
                    array('cidr', $opts['net']), $opts['cst'], $token_file) ;
                $reply = $rest_api->get_result() ;
                if ($reply['data']) {
                    foreach ($reply['data'] as $subnet) {
                        $renderer->doc .= $this->showSub((array)$subnet, $opts['fmt']) ;
                    }
                } else {
                    $renderer->doc .= $this->showErr($reply, $opts) ;
                }
            } elseif ($opts['d2s']) { // Subnets from Device index
                $rest_api->execute('GET', 'tools',
                    array('devices', $opts['d2s'], 'subnets'), $opts['cst'], $token_file) ;
                $reply = $rest_api->get_result() ;
                if ($reply['data']) {
                    foreach ($reply['data'] as $subnet) {
                        $renderer->doc .= $this->showSub((array)$subnet, $opts['fmt']) ;
                    }
                } else {
                    $renderer->doc .= $this->showErr($reply, $opts) ;
                }
            } elseif ($opts['f2s']) { // Subnets from Folder index
                $rest_api->execute('GET', 'subnets',
                    array($opts['f2s'], 'slave_recursive'), $opts['cst'], $token_file) ;
                $reply = $rest_api->get_result() ;
                if ($reply['data']) {
                    foreach ($reply['data'] as $subnet) {
                        $renderer->doc .= $this->showSub((array)$subnet, $opts['fmt']) ;
                    }
                } else {
                    $renderer->doc .= $this->showErr($reply, $opts) ;
                }
            } elseif ($opts['l2s']) { // Subnets from Location index
                $rest_api->execute('GET', 'tools',
                    array('locations', $opts['l2s'], 'subnets'), $opts['cst'], $token_file) ;
                $reply = $rest_api->get_result() ;
                if ($reply['data']) {
                    foreach ($reply['data'] as $subnet) {
                        $renderer->doc .= $this->showSub((array)$subnet, $opts['fmt']) ;
                    }
                } else {
                    $renderer->doc .= $this->showErr($reply, $opts) ;
                }
            } elseif ($opts['r2s']) { // Subnets from VRF index
                $rest_api->execute('GET', 'vrf',
                        array($opts['r2s'], 'subnets'), $opts['cst'], $token_file) ;
                $reply = $rest_api->get_result() ;
                if ($reply['data']) {
                    foreach ($reply['data'] as $subnet) {
                        $renderer->doc .= $this->showSub((array)$subnet, $opts['fmt']) ;
                    }
                } else {
                    $renderer->doc .= $this->showErr($reply, $opts) ;
                }
            } elseif ($opts['s2s']) { // Subnets from Section index
                $rest_api->execute('GET', 'sections',
                    array($opts['s2s'], 'subnets'), $opts['cst'], $token_file) ;
                $reply = $rest_api->get_result() ;
                if ($reply['data']) {
                    foreach ($reply['data'] as $subnet) {
                        $renderer->doc .= $this->showSub((array)$subnet, $opts['fmt']) ;
                    }
                } else {
                    $renderer->doc .= $this->showErr($reply, $opts) ;
                }
            } elseif ($opts['v2s']) { // Subnets from VLAN index
                $rest_api->execute('GET', 'vlans',
                    array($opts['v2s'], 'subnets'), $opts['cst'], $token_file) ;
                $reply = $rest_api->get_result() ;
                if ($reply['data']) {
                    foreach ($reply['data'] as $subnet) {
                        $renderer->doc .= $this->showSub((array)$subnet, $opts['fmt']) ;
                    }
                } else {
                    $renderer->doc .= $this->showErr($reply, $opts) ;
                }
            } elseif (isset($opts['vid'])) { // VLAN index
                if ($opts['vid']) {
                    $rest_api->execute('GET', 'vlans',
                        array($opts['vid']), array(), $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        $renderer->doc .= $this->showNet((array)$reply['data'], $opts['fmt']) ;
                    } else {
                         $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                } else {
                    $rest_api->execute('GET', 'vlans',
                        array(), $opts['cst'], $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        foreach ((array)$reply['data'] as $vlan) {
                            $renderer->doc .= $this->showNet((array)$vlan, $opts['fmt']) ;
                        }
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                }
            } elseif (isset($opts['vlan'])) { // VLAN number
                $rest_api->execute('GET', 'vlans',
                    array('search', $opts['vlan']), $opts['cst'], $token_file) ;
                $reply = $rest_api->get_result() ;
                if ($reply['data']) {
                    foreach ($reply['data'] as $vlan) {
                        $renderer->doc .= $this->showNet((array)$vlan, $opts['fmt']) ;
                    }
                } else {
                    $renderer->doc .= $this->showErr($reply, $opts) ;
                }
            } elseif ($opts['22v']) { // VLAN from level2 domain index
                $rest_api->execute('GET', 'l2domains',
                    array($opts['22v'], 'vlans'), $opts['cst'], $token_file) ;
                $reply = $rest_api->get_result() ;
                if ($reply['data']) {
                    foreach ($reply['data'] as $vlan) {
                        $renderer->doc .= $this->showNet((array)$vlan, $opts['fmt']) ;
                    }
                } else {
                    $renderer->doc .= $this->showErr($reply, $opts) ;
                }
            } elseif (isset($opts['2id'])) { // level2 domain index
                if ($opts['2id']) {
                    $rest_api->execute('GET', 'l2domains',
                        array($opts['2id']), array(), $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        $renderer->doc .= $this->showDom((array)$reply['data'], $opts['fmt']) ;
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                } else {
                    $rest_api->execute('GET', 'l2domains',
                        array(), $opts['cst'], $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        foreach ($reply['data'] as $l2dom) {
                            $renderer->doc .= $this->showDom((array)$l2dom, $opts['fmt']) ;
                        }
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                }
            } elseif (isset($opts['rid'])) { // VRF index
                if ($opts['rid']) {
                    $rest_api->execute('GET', 'vrf',
                        array($opts['rid']), array(), $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        $renderer->doc .= $this->showFwd((array)$reply['data'], $opts['fmt'], 1) ;
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                } else {
                    $rest_api->execute('GET', 'vrf',
                        array(), $opts['cst'], $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        foreach ($reply['data'] as $vrf) {
                            $renderer->doc .= $this->showFwd((array)$vrf, $opts['fmt'], 1) ;
                        }
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                }
            } elseif (isset($opts['vrf'])) { // VRF index
                if ($opts['vrf']) {
                    $rest_api->execute('GET', 'vrf',
                        array($opts['vrf']), array(), $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        $renderer->doc .= $this->showFwd((array)$reply['data'], $opts['fmt'], 0) ;
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                } else {
                    $rest_api->execute('GET', 'vrf',
                        array(), $opts['cst'], $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        foreach ($reply['data'] as $vrf) {
                            $renderer->doc .= $this->showFwd((array)$vrf, $opts['fmt'], 0) ;
                        }
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                }
            } elseif (isset($opts['eid'])) { // Rack index
                if ($opts['eid']) {
                    $rest_api->execute('GET', 'tools',
                        array('racks', $opts['eid']), array(), $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        $renderer->doc .= $this->showCab((array)$reply['data'], $opts['fmt']) ;
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                } else {
                    $rest_api->execute('GET', 'tools',
                        array('racks'), $opts['cst'], $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        foreach ($reply['data'] as $rack) {
                            $renderer->doc .= $this->showCab((array)$rack, $opts['fmt']) ;
                        }
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                }
            } elseif (isset($opts['d2e'])) { // Rack from Device index
                if ($opts['d2e']) {
                    $rest_api->execute('GET', 'devices',
                        array($opts['d2e']), array(), $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        $device = (array)$reply['data'] ;
                        $renderer->doc .= $this->showCab($device['rack'], $opts['fmt']) ;
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                } else {
                    $rest_api->execute('GET', 'devices',
                        array(), $opts['cst'], $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        $device = (array)$reply['data'] ;
                        $renderer->doc .= $this->showCab($device['rack'], $opts['fmt']) ;
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                }
            } elseif (isset($opts['l2e'])) { // Rack from Location index
                $rest_api->execute('GET', 'tools',
                    array('locations', $opts['l2e'], 'racks'), $opts['cst'], $token_file) ;
                $reply = $rest_api->get_result() ;
                if ($reply['data']) {
                    foreach ($reply['data'] as $rack) {
                        $renderer->doc .= $this->showCab((array)$rack, $opts['fmt']) ;
                    }
                } else {
                    $renderer->doc .= $this->showErr($reply, $opts) ;
                }
            } elseif (isset($opts['lid'])) { // Location index
                if ($opts['lid']) {
                    $rest_api->execute('GET', 'tools',
                        array('locations', $opts['lid']), array(), $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        $renderer->doc .= $this->showLoc((array)$reply['data'], $opts['fmt'], 0) ;
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                } else {
                    $rest_api->execute('GET', 'tools',
                        array('locations'), $opts['cst'], $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        foreach ($reply['data'] as $location) {
                            $renderer->doc .= $this->showLoc((array)$location, $opts['fmt'], 0) ;
                        }
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                }
            } elseif (isset($opts['loc'])) { // Location index
                if ($opts['loc']) {
                    $rest_api->execute('GET', 'tools',
                        array('locations', $opts['loc']), array(), $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        $renderer->doc .= $this->showLoc((array)$reply['data'], $opts['fmt'], 1) ;
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                } else {
                    $rest_api->execute('GET', 'tools',
                        array('locations'), $opts['cst'], $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        foreach ($reply['data'] as $location) {
                            $renderer->doc .= $this->showLoc((array)$location, $opts['fmt'], 1) ;
                        }
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                }
            } elseif ($opts['did']) { // Device index
                if ($opts['did']) {
                    $rest_api->execute('GET', 'tools',
                        array('devices', $opts['did']), array(), $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        $renderer->doc .= $this->showDev((array)$reply['data'], $opts['fmt']) ;
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                } else {
                    $rest_api->execute('GET', 'tools',
                        array('devices'), $opts['cst'], $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        foreach ($reply['data'] as $device) {
                            $renderer->doc .= $this->showDev((array)$device, $opts['fmt']) ;
                        }
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                }
            } elseif ($opts['l2d']) { // Devices from Location index
                $rest_api->execute('GET', 'tools',
                    array('locations', $opts['l2d'], 'devices'), array(), $token_file) ;
                $reply = $rest_api->get_result() ;
                if (!empty($reply['data'])) {
                    foreach ($reply['data'] as $device) {
                        $renderer->doc .= $this->showDev((array)$device, $opts['fmt']) ;
                    }
                } else {
                    $renderer->doc .= $this->showErr($reply, $opts) ;
                }
            } elseif ($opts['e2d']) { // Devices from Rack index
                $rest_api->execute('GET', 'tools',
                    array('racks', $opts['e2d'], 'devices'), $opts['cst'], $token_file) ;
                $reply = $rest_api->get_result() ;
                if ($reply['data']) {
                    foreach ($reply['data'] as $device) {
                        $renderer->doc .= $this->showDev((array)$device, $opts['fmt']) ;
                    }
                } else {
                    $renderer->doc .= $this->showErr($reply, $opts) ;
                }
            } elseif (isset($opts['tid'])) { // Tag index
                if ($opts['tid']) {
                    $rest_api->execute('GET', 'tools',
                        array('tags', $opts['tid']), array(), $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        $renderer->doc .= $this->showTag((array)$reply['data'], $opts['fmt']) ;
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                } else {
                    $rest_api->execute('GET', 'tools',
                        array('tags'), $opts['cst'], $token_file) ;
                    $reply = $rest_api->get_result() ;
                    if ($reply['data']) {
                        foreach ($reply['data'] as $tag) {
                            $renderer->doc .= $this->showTag((array)$tag, $opts['fmt']) ;
                        }
                    } else {
                        $renderer->doc .= $this->showErr($reply, $opts) ;
                    }
                }
            } elseif ($opts['t2d']) { // Devices from their Type index
                $rest_api->execute('GET', 'tools',
                    array('device_types', $opts['t2d'], 'devices'), array(), $token_file) ;
                $reply = $rest_api->get_result() ;
                if (!$reply['data']) {
                    $rest_api->execute('GET', 'tools',
                        array('devicetypes', $opts['t2d'], 'devices'), array(), $token_file) ;
                    $reply = $rest_api->get_result() ;
                }
                if ($reply['data']) {
                    foreach ($reply['data'] as $device) {
                        $renderer->doc .= $this->showDev((array)$device, $opts['fmt']) ;
                    }
                }
            } else {
                $renderer->doc .= "<p class='error'>unknown controller... </p>" ;
            }
            return true ;
        } else {
            // $mode is 'metadata' (or some newer other value),
            // or $opts is unset (en empty string or zero)
            return false ;
        }
    }

    /**
     * Display  API query Error
     *
     * @param  $result  mixed   IPAM result data value
     * @param  $input   mixed   parameters passed to rendering
     * @retval $web_out string  HTML part for the wiki
     */
    private function showErr($result, $input) {
        // TITLE
        if ($this->getConf('opo_dat')) {
            $web_out = '<h6>{php}<u>IPAM</u></h6>' ;
        }
        if ($this->getConf('allowdebug')) {
            $web_out .= '<dl class="error"><dt>Parameters:</dt><dd><pre>' . var_export($input, true) . '</pre></dd>' ;
            $web_out .= '<dt>Result:</dt><dd><pre>' . var_export($result, true) . '</pre></dd></dl>' ;
        } else {
            $web_out .= "<p class='error'>Error" . $result['code'] . ': ' . $result['message'] . '</p>' ;
        }
        return "$web_out" ;
    }

    /**
     *
     * @param  $_path  string  direct link to the object page
     * @note two kind of links:
     * @note 'index.php?page=tools&section=NAME&subnetId=1&sPage=NUMBER'
     * @note 'index.php?page=administration&section=NAME&subnetId=1&sPage=NUMBER'
     *
     * @see ::showSub
     * @see ::showLoc
     * @see ::showDev
     */

    /**
     * Build web link from a given address
     *
     * @param  $href string  destination URL
     * @param  $clic string  optional content
     * @retval $html string  formated output
     */
    private function setALink($href, $clic) {
        $html = "<a href='$href' class='" ;
        $_href = parse_url($href) ;
        // https://forum.dokuwiki.org/post/14876
        if ($_href['scheme']) { // absolute URL
            if ($_href['scheme'] == 'mailto') {
                $html .= "mail' title='" . substr($href, 6) ;
            } elseif ($_href['scheme'] == 'smb') {
                $html .= "windows' title='" . substr($href, 3) ;
            } elseif ($_href['scheme'] == 'file' || $_href['scheme'] == 'cifs') {
                $html .= "windows' title='" . substr($href, 4) ;
            } else {
                $_wiki = '' ;  // current InterWiki
                // https://dokuwiki.org/interwiki
                // https://dokuwiki.org/tips:interwiki_shortcuts
                foreach (getInterWiki() as $_key => $_val) {
                    if (strpos($href, strtok($_val, '{'), 0) !== false) {
                        $_wiki = $_key ;
                        break ;
                    }
                }
                if ($_wiki == 'this') {
                    if ($_href['path'] == '/doku.php') {
                        parse_str($_href['query'], $_arg) ;
                        $html .= 'wikilink' . (page_exists($_arg['id']) ? '1' : '2') ;
                        $html .= "' title='" . $_arg['id'] ;
                        unset($_arg) ;
                    } else {
                        if (file_exists(DOKU_INC . 'data/pages' . $_href['path'] . '.txt')) {
                            $html .= "wikilink1' title='" . str_replace('/', ':', ltrim($_href['path'], '/')) ;
                        } elseif (file_exists(rtrim(DOKU_INC, '/') . $_href['path'])) {
                            $html .= "interwiki iw_this' title='" . $_href['path'] ;
                        } else {
                            $html .= "wikilink2' title='" . str_replace('/', ':', ltrim($_href['path'], '/')) ;
                        }
                    }
                } else {
                    $html .=  ($_wiki ? "interwiki iw_$_wiki" : 'urlextern') ;
                }
            }
        } else { // relative URL
            if ($_href['path'] == '/doku.php') {
                // https://www.dokuwiki.org/pagename
                parse_str($_href['query'], $_arg) ;
                $_page = $_arg['id'] ;
            } else {
                // https://www.dokuwiki.org/config:useslash
                $_page = str_replace('/', ':', $_href['path'], $_nbr) ;
            }
            // https://forum.dokuwiki.org/post/61789
            $html .= 'wikilink' . (page_exists($_page) ? '1' : '2') ;
            $html .= "' title='$_page" ;
        }
        $html .= "'>" ;
        if ($clic)
            $html .= "$clic</a>" ;
        return "$html" ;
    }

    /**
     * Display IP addresses list
     *
     * @param  $list mixed   IPAM result data value
     * @retval $html string  HTML part for the wiki
     */
    private function listAddrs($list) {
        if (empty($list)) {
            if ($this->getConf('allowdebug')) {
                return "<p class='level4 warning'>" . "No Address" . '</p>' ;
            } else 
                return '' ;
        }
        $html = '' ;
        $_conf = trim($this->getConf('opo_efa')) ;
        // open listing
        switch($this->getConf('opo_lst')) {
            case 'dl' :
                $html .= "\n\t<dl class='inline phpipam_address' style='margin: .1em 0; padding: 0;'>" ;
                $_row = 0 ;
                break ;
            case 'tr' :
                $html .= "\n\t<table class='inline phpipam_address'>" ;
                $html .= "\n\t<thead>\n\t\t<tr class='row0'>" ;
                $html .= "\n\t\t\t<th class='col0'>" . $this->getLang('ip') . '</th>' ;
                $html .= "\n\t\t\t<th class='col1'>" . $this->getLang('hostname') . '</th>' ;
                $html .= "\n\t\t\t<th class='col2'>" . $this->getLang('description') . '</th>' ;
                if ($this->getConf('opo_efa')) {
                    $_col = 3 ;
                    $_more = array() ;
                    foreach (explode(',', $_conf) as $_key) {
                        $html .= "\n\t\t\t<th class='col$_col'>" ;
                        $_lang = $this->getLang($_key) ;
                        $html .= ($_lang ? "$_lang" : ucwords(strtr($_key, '_', ' '))) ;
                        $html .= '</th>' ;
                        $_col += 1 ;
                    }
                    unset($_col, $_lang, $_more) ;
                }
                $html .= "\n\t\t</tr>\n\t</thead>\n\t<tbody>" ;
                $_row = 1 ;
                break ;
            case 'ul' :
                $html .= "\n\t<ul class='phpipam_address'>" ;
                break ;
            case 'p' :
                $html .= "\n\t<div class='phpipam_address'>" ;
                break ;
            case 'pre' :
                $html .= "\n\t<pre class='code text phpipam_address'>" ;
                break ;
        }
        // prepare tags list
        $rest_api = new phpipam_api_client(
            $this->getConf('api_url'), $this->getConf('api_app'),
            $this->getConf('api_key') ? $this->getConf('api_key') : false,
            $this->getConf('api_usr'), $this->getConf('api_pwd'), 'array') ;
        $token_file = ($this->getConf('api_taf') ? 'token' : false) ;
        $rest_api->execute('GET', 'tools',
            array('tags'), array(), $token_file) ;
        $reply = $rest_api->get_result() ;
        if (isset($reply['data'])) {
            foreach ((array)$reply['data'] as $_val) {
                $_val = (array)$_val ;
                $_plus[$_val['id']] = array(
                    $_val['type'],
                    $_val['showtag'],
                    $_val['bgcolor'],
                    $_val['fgcolor'],
                ) ;
            }
        } else {
            $_plus = array(
                1 => array("Offline",  1, '#f59c99', '#ffffff'),
                2 => array("Used",     0, '#a9c9a4', '#ffffff'), // online
                3 => array("*",        1, '#9ac0cd', '#ffffff'), // reserved
                4 => array("DHCP",     1, '#c9c9c9', '#ffffff'),
            ) ;
        }
        // iterate on entries
        foreach ($list as $address) {
            $infos = (array)$address ;
            // prepare essential part
            if ($this->getConf('opo_eca')) {
                $_css = " style='background-color: " . $_plus[$infos['tag']][2] ;
                $_css .= "; color: " . $_plus[$infos['tag']][3] . ';' ;
                $_tag0 = $_tag1 = '' ;
            } else
            switch ($infos['tag']) {
                case 1 :
                    $_tag1 = '<del>' ;
                    $_tag0 = '</del>' ;
                    break ;
                default :
                    if ($_plus[$infos['tag']][1])
                        $_tag1 = '[' . $_plus[$infos['tag']][0] . '] ' ;
                    else
                        $_tag1 = '' ;
                    $_tag0 = '' ;
                    break ;
            }
            $_url = $this->getConf('opo_cf1') ;
            if ($_url && $infos["$_url"]) {
                $_hAddr = $this->setALink($infos["$_url"], $infos['ip']) ;
            } else {
                $_hAddr = $infos['ip'] ;
            }
            $_hName = '' ;
            $_url = $this->getConf('opo_cf2') ;
            if ($_url && $infos["$_url"]) {
                $_hName .= $this->setALink($infos["$_url"]) ;
            }
            $_dom = $this->getConf('opo_mdn') ;
            if ($_dom && strrpos($infos['hostname'], $_dom)) {
                $_len = strlen($_dom)+1 ;
                $_hName .= substr_replace($infos['hostname'], '', -$_len, $_len) ;
            } else {
                $_hName .= $infos['hostname'] ;
            }
            if ($_url && $infos["$_url"]) {
                $_hName .= '</a>' ;
            }
            // prepare requested parts
            if ($_conf) {
                $_more = array() ;
                foreach (explode(',', $_conf) as $_key) {
                    if ($infos[$_key]) {
                        $_more[$_key][1] = hsc($infos[$_key]) ;
                        $_lang = $this->getLang($_key) ;
                        $_more[$_key][0] = ($_lang ? "$_lang" : ucwords(strtr($_key, '_', ' '))) ;
                    }
                }
            }
            // build the output
            switch($this->getConf('opo_lst')) {
                case 'dl' :
                    if (!$_css)
                        $_css = "style='" ;
                    else
                        $_dd = "border-left: 1px solid #0000;" ;
                    $_dd .= " top: 0; margin: 0 0 0 12em; padding: 0 0 .5em .5em;" ;
                    $html .= "\n\t\t<dt class='row$_row phpipam_address_ip'$_css position: relative; left: 0; top: 1.3em; width: 12em; font-weight: bold;'>" ;
                    $html .= $_tag1 . $_hAddr .  $_tag0 . '</dt>' ;
                    if ($infos['hostname']) {
                        $html .= "\n\t\t<dd class='row$_row phpipam_address_hostname'$_css$_dd'>" ;
                        $html .= $_hName . '</dd>' ;
                    }
                    if ($infos['description']) {
                        $html .= "\n\t\t<dd class='row$_row phpipam_address_description'$_css$_dd'>" ;
                        $html .= hsc($infos['description']) . '</dd>' ;
                    }
                    if (count($_more)) {
                        $html .= "\n\t\t<dd class='row$_row'$_css'>" ;
                        $html .= "\n\t\t\t<dl $_css$_dd'>" ;
                        foreach ($_more as $_key => $_val) {
                            $html .= "\n\t\t\t\t<dt class='row$_row phpipam_address_$_key'>" ;
                            $html .= $_val[0] . '</dt>' ;
                            $html .= "\n\t\t\t\t<dd class='row$_row phpipam_address_$_key' style='margin-left: 1.2em;'>" ;
                            $html .= $_val[1] . '</dd>' ;
                            $_row += 1 ;
                        }
                        $html .= "\n\t\t\t</dl>" ;
                        $html .= "\n\t\t</dd>" ;
                    }
                    $html .= "\n\t</dl>" ;
                    $_row += 1 ;
                    break ;
                case 'tr' :
                    $html .= "\n\t\t<tr class='row$_row phpipam_tag-" ;
                    $html .= $infos['tag'] . "'$_css'>" ;
                    $html .= "\n\t\t\t<th class='col0 phpipam_address_ip'>" ;
                    $html .= $_tag1 . $_hAddr .  $_tag0 . '</th>' ;
                    $html .= "\n\t\t\t<td class='col1 phpipam_address_hostname'>" ;
                    $html .= $_hName . '</td>' ;
                    $html .= "\n\t\t\t<td class='col2 phpipam_address_description'>" ;
                    $html .= hsc($infos['description']) . '</td>' ;
                    if ($_conf) {
                        $_col = 3 ;
                        foreach (explode(',', $_conf) as $_key) {
                            $html .= "\n\t\t\t<td class='col$_col phpipam_address_hostname'>" ;
                            $html .= $_more[$_key][1] . '</td>' ;
                            $_col += 1 ;
                        }
                        unset($_col) ;
                    }
                    $html .= "\n\t\t</tr>" ;
                    $_row += 1 ;
                    break ;
                case 'ul' :
                    $html .= "\n\t\t<li class='level4 phpipam_tag-" ;
                    $html .= $infos['tag'] . "'$_css'><div class='li'>" ;
                    $html .= $_tag1 ;
                    $html .= "<span class='phpipam_address_ip'>" ;
                    $html .= $_hAddr . '</span>' ;
                    if ($infos['hostname']) {
                        $html .= ' &#8596; ' ;
                        $html .= "<span class='phpipam_address_hostname'>" ;
                        $html .= $_hName . '</span>' ;
                    }
                    $html .= $_tag0 ;
                    if ($infos['description']) {
                        $html .= " <em class='phpipam_address_description'>" ;
                        $html .= hsc($infos['description']) . '</em>' ;
                    }
                    if (count($_more)) {
                        $html .= "\n\t\t\t<ul><div class='li'>" ;
                        foreach ($_more as $_key => $_val) {
                            $html .= "\n\t\t\t\t<li class='level5 phpipam_address_$_key'>" ;
                            $html .= "<div class='li'>" ;
                            $html .= "<span class='label'>" . $_val[0] . "</span>: " ;
                            $html .= "<span class='value'>" . $_val[1] . "</span>" ;
                            $html .= "\n\t\t\t\t</div></li>" ;
                        }
                        $html .= "\n\t\t\t</div></ul>" ;
                    }
                    $html .= "\n\t\t</li>" ;
                    break ;
                case 'p' :
                    $html .= "\n\t\t<p class='level4 phpipam_tag-" ;
                    $html .= $infos['tag'] . "'$_css'>" ;
                    $html .= $_tag1 ;
                    $html .= "<span class='phpipam_address_ip'>" ;
                    $html .= $_hAddr . '</span>' ;
                    if ($infos['hostname']) {
                        $html .= ' &#8596; ' ;
                        $html .= "<span class='phpipam_address_hostname'>" ;
                        $html .= $_hName . '</span>' ;
                    }
                    $html .= $_tag0 ;
                    if ($infos['description']) {
                        $html .= " <em class='phpipam_address_description'>" ;
                        $html .= hsc($infos['description']) . '</em>' ;
                    }
                    if (count($_more)) {
                        foreach ($_more as $_key => $_val) {
                            $html .= "\n\t\t\t\t<br />" ;
                            $html .= "<span class='phpipam_address_$key'>" . $_val[0] . "</span>: " ;
                            $html .= "<span class='phpipam_address_$key'>" . $_val[1] . "</span>" ;
                        }
                    }
                    $html .= "\n\t</p>" ;
                    break ;
                case 'pre' :
                    $html .= $_tag1 ;
                    $html .= "<span class='phpipam_address_ip'$_css'>" ;
                    $html .= $_hAddr . '</span>' ;
                    $html .= ' = ' ;
                    $html .= "<span class='phpipam_address_hostname'$_css'>" ;
                    $html .= $_hName . '</span>' ;
                    $html .= $_tag0 ;
                    $html .= "\t<em class='phpipam_address_description'$_css'>" ;
                    $html .= hsc($infos['description']) . '</em>' ;
                    if ($_conf) {
                        foreach (explode(',', $this->getConf('opo_efs')) as $_key) {
                            $html .= "\t<span class='phpipam_address_$_key'$_css'>" ;
                            $html .= $_val[1] . '</span>' ;
                        }
                    }
                    $html .= "\n" ;
                    break ;
            }
            // that's all folks
        }
        // close listing
        switch($this->getConf('opo_lst')) {
            case 'dl' :
                $html .= "\n\t</dl>" ;
                break ;
            case 'tr' :
                $html .= "\n\t</tbody>\n\t</table>" ;
                break ;
            case 'ul' :
                $html .= "\n\t</ul>" ;
                break ;
            case 'p' :
                $html .= "\n\t</div>" ;
                break ;
            case 'pre' :
                $html .= "</pre>\n" ;
                break ;
        }
        return "$html" ;
    }

    /**
     * Display an object properties
     *
     * @param  $list mixed  Properties list
     * each entry is: 'property' => array("label", "value")
     * @param  $type string Object type name
     * @retval $html string Web fragment output
     */
    private function listProps($list, $type) {
        if (empty($list)) {
            if ($this->getConf('allowdebug')) {
                return "<p class='level4 warning'>" . "No Property" . '</p>' ;
            } else 
                return '' ;
        }
        $_row = 0 ;
        switch($this->getConf('opo_dsc')) {
            case 'dl' :
                $html = "\n\t<dl class='inline phpipam_${type}s' style='width: 100%; margin: 2em 0; padding: 0;'>" ;
                foreach ($list as $_key => $_val) {
                    $html .= "\n\t\t<dt class='row$_row col0 phpipam_${type}_$_key' style='font-weight: bold;'>" ;
                    $html .= $_val[0] . '</dt>' ;
                    $html .= "\n\t\t<dd class='row$_row col1 phpipam_${type}_$_key level3'>" ;
                    $html .= $_val[1] . '</dd>' ;
                    $_row += 1 ;
                }
                $html .= "\n\t</dl>" ;
                break ;
            case 'tr' :
                $html = "\n\t<table class='inline phpipam_${type}s'>\n\t<tbody>" ;
                foreach ($list as $_key => $_val) {
                    $html .= "\n\t\t<tr class='row$_row phpipam_${type}_$_key'>" ;
                    $html .= "\n\t\t\t<th class='col0 rightalign'>" . $_val[0] . '</th>' ;
                    $html .= "\n\t\t\t<td class='col1 leftalign'>" . $_val[1] . '</td>' ;
                    $html .= "\n\t\t</tr>" ;
                    $_row += 1 ;
                }
                $html .= "\n\t</tbody>\n\t</table>" ;
                break ;
            case 'ul' :
                $html = "\n\t<ul class='inline phpipam_${type}s'>" ;
                foreach ($list as $_key => $_val) {
                    $html .= "\n\t\t<li class='level3 row$_row phpipam_${type}_$_key'>" ;
                    $html .= "\n\t\t\t<div class='li'>" ;
                    $html .= "<b class='col0'>" . $_val[0] . '</b>:' ;
                    $html .= " <span class='col1'>" . $_val[1] . '</span>' ;
                    $html .= "\n\t\t\t</div>" ;
                    $html .= "\n\t\t</li>" ;
                    $_row += 1 ;
                }
                $html .= "\n\t</ul>" ;
                break ;
            case 'p' :
                $html = "\n\t<div class='inline phpipam_${type}s'>" ;
                foreach ($list as $_key => $_val) {
                    $html .= "\n\t\t<p class='level3 row_$_row phpipam_${type}_$_key'>" ;
                    $html .= "\n\t\t<b class='col0'>" . $_val[0] . '</b>:' ;
                    $html .= " <span class='col1'>" . $_val[1] . '</span>' ;
                    $html .= "\n\t\t</p>" ;
                    $_row += 1 ;
                }
                $html .= "\n\t</div>" ;
                break ;
            case 'pre' :
                $html = "\n\t<pre class='code text phpipam_${type}s'>" ;
                foreach ($list as $_key => $_val) {
                    $html .= "<span class='row_$_row phpipam_${type}_$_key'>" ;
                    $html .= "<b class='col0'>" . $_val[0] . '</b>' ;
                    $html .= "\t<span class='col1'>" . $_val[1] . '</span>' ;
                    $html .= "</span>\n" ;
                    $_row += 1 ;
                }
                $html .= "</pre>\n" ;
                break ;
        }
        unset($_row) ;
        return "$html" ;
    }

    /**
     * Diplay Subnetwork and/or linked addresses
     *
     * @param  $hash_in mixed   IPAM result data value
     * @param  $show_it integer for part to display
     * @retval $web_out string  HTML part for the wiki
     */
    private function showSub($hash_in, $show_it) {
        $web_out = "\n<div class='phpipam phpipam_subnet-" . $hash_in['id'] ;
        $web_out .= " phpipam_section-" . $hash_in['sectionId'] . "'>" ;
        $_host = $this->getConf('opo_url') ;
        $rest_api = new phpipam_api_client(
            $this->getConf('api_url'), $this->getConf('api_app'),
            $this->getConf('api_key') ? $this->getConf('api_key') : false,
            $this->getConf('api_usr'), $this->getConf('api_pwd'), 'array') ;
        $token_file = ($this->getConf('api_taf') ? 'token' : false) ;
        // TITLE
        if ($this->getConf('opo_dat')) {
            $_path = 'index.php?page=' . ($hash_in['isFolder'] ? 'folders' : 'subnets') . '&section=' . $hash_in['sectionId'] . '&subnetId=' . $hash_in['id'] ;
            $web_out .= "\n\t<h6 class='subnet_name'>" ;
            if ($_host) {
                $web_out .= $this->setALink("$_host$_path", $hash_in['description']) ;
            } else {
                $web_out .= $hash_in['description'] ;
            }
            $web_out .= "</h6>" ;
        }
        // HEAD
        if ($show_it <= 0) {
            $_more = array() ;
            $_conf = trim($this->getConf('opo_efs')) ;
            // prepare some specific parts
            $_path = 'index.php?page=' . ($hash_in['isFolder'] ? 'folders' : 'subnets') . '&section=' . $hash_in['sectionId'] . '&subnetId=' . $hash_in['id'] ;
            if ($hash_in['calculation']) { // v3 subnet
                $infos = (array)$hash_in['calculation'] ;
                $_more['subnet'][1] .= "<span title='" . $infos['Subnet Class'] ;
                $_more['subnet'][1] .= "'>" . $hash_in['subnet'] . '</span>/' ;
                $_more['subnet'][1] .= "<span title='" . $infos['Subnet netmask'] ;
                $_more['subnet'][1] .= "'>" . $hash_in['mask'] . '</span>' ;
                unset($infos) ;
            } elseif ($hash_in['isFolder']) { // any folder
                $_more['subnet'][1] .= $this->getLang('isFolder') ;
            } else { // v2 subnet
                $_more['subnet'][1] .= $hash_in['subnet']. '/' . $hash_in['mask'] ;
            }
            if ($_host)
                $_more['subnet'][1] = $this->setALink("$_host$_path", $_more['subnet'][1]) ;
            $_more['subnet'][0] = $this->getLang('subnet') ;
            if (preg_match('/\bsubnet\b/', $_conf))
                unset($_more['subnet']) ; // array_pop($_more) ;
            if ($hash_in['vlanId'] && !preg_match('/\bvlanId\b/', $_conf) ) {
                $_path = 'index.php?page=tools&section=vlan' ;
                $_path .= '&subnetId=' . $hash_in['domainId'] ;
                $_path .= '&sPage=' . $hash_in['vlanId'] ;
                $rest_api->execute('GET', 'vlan',
                    array($hash_in['vlanId']), array(), $token_file) ;
                $reply = $rest_api->get_result() ;
                if ($reply['data']) {
                    $reply = (array)$reply['data'] ;
                    $_more['vlanId'][1] = "<span title='" . $reply['name'] . "'>" ;
                    $_more['vlanId'][1] .= $reply['number'] . '</span>' ;
                    if ($_host)
                        $_more['vlanId'][1] = $this->setALink("$_host$_path", $_more['vlanId'][1]) ;
                    $_more['vlanId'][0] = $this->getLang('vlan') ;
                }
                unset($_path) ;
            }
            if ($hash_in['vrfId'] && !preg_match('/\bvrfId\b/', $_conf) ) {
                $_path = 'index.php?page=tools&section=vrf&subnetId=1&sPage=' . $hash_in['vlanId'] ;
                $rest_api->execute('GET', 'vrf',
                    array($hash_in['vrfId']), array(), $token_file) ;
                $reply = $rest_api->get_result() ;
                if ($reply['data']) {
                    $reply = (array)$reply['data'] ;
                    $_more['vrfId'][1] = "<span title='rd: " . $reply['rd'] . "'>" ;
                    $_more['vrfId'][1] .= $reply['name'] . '</span>' ;
                    if ($_host)
                        $_more['vrfId'][1] = $this->setALink("$_host$_path", $_more['vrfId'][1]) ;
                    $_more['vrfId'][0] = $this->getLang('vrf') ;
                }
            }
            if ($hash_in['gateway'] && !preg_match('/\bgateway\b/', $_conf) ) { // v3
                $_infos = (array)$hash_in['gateway'] ;
                $_more['gateway'][1] = $_infos['ip_addr'] ;
                $_more['gateway'][0] = $this->getLang('gateway') ;
                unset($_path, $_infos) ;
            }
            if ($hash_in['nameserverId'] && !preg_match('/\bnameserverId\b/', $_conf) ) {
                $_path = 'index.php?page=tools&section=nameservers&subnetId=1&sPage=' . $hash_in['vlanId'] ;
                $rest_api->execute('GET', 'tools',
                    array('nameservers', $hash_in['nameserverId']), array(), $token_file) ;
                $reply = $rest_api->get_result() ;
                if ($reply['data']) {
                    $reply = (array)$reply['data'] ;
                    $_more['nameserverId'][1] = "<span title='" . $reply['name'] . "'>" ;
                    $_more['nameserverId'][1] .= str_replace(':', ' ', 
                        $reply['namesrvl']) . '</span>' ;
                    if ($_host)
                        $_more['nameserverId'][1] = $this->setALink("$_host$_path", $_more['nameserverId'][1]) ;
                    $_more['nameserverId'][0] = $this->getLang('namesrvl') ;
                }
                unset($_path, $reply) ;
            }
            // prepare requested parts
            if ($_conf) {
                foreach (explode(',', $_conf) as $_key) {
                    if ($hash_in[$_key]) {
                        $_more[$_key][1] = hsc($hash_in[$_key]) ;
                        $_lang = $this->getLang($_key) ;
                        $_more[$_key][0] = ("$_lang" ? "$_lang" : ucwords(strtr($_key, '_', ' '))) ;
                    }
                }
            }
            // build the output
            $web_out .= $this->listProps($_more, 'subnet') ;
            unset($_more, $_conf, $_host) ;
        }
        // TAIL
        if ($show_it >= 0) {
            $rest_api->execute('GET', 'subnets',
                array($hash_in['id'], 'addresses'), array(), $token_file) ;
            $reply = $rest_api->get_result() ;
            if (isset($reply['data'])) {
                $web_out .= $this->listAddrs((array)$reply['data']) ;
            } elseif ($this->getConf('allowdebug')) {
                $web_out .= '<p class="level4">' . $reply['message'] . '</p>' ;
            }
        }
        $web_out .= "\n</div>" ;
        return "$web_out" ;
    }

    /**
     * Display Device and/or linked addresses
     *
     * @param  $hash_in mixed   IPAM result data value
     * @param  $show_it integer for part to display
     * @retval $web_out string  HTML part for the wiki
     */
    private function showDev($hash_in, $show_it) {
        $web_out = "\n<div class='phpipam phpipam_device-" . $hash_in['id'] ;
        foreach (explode(',', $hash_in['sections']) as $_section) {
            $web_out .= " phpipam_section-" . $_section ;
        }
        $web_out .= " phpipam_device_type-" . $hash_in['type'] . "'>" ;
        $_host = $this->getConf('opo_url') ;
        $rest_api = new phpipam_api_client(
            $this->getConf('api_url'), $this->getConf('api_app'),
            $this->getConf('api_key') ? $this->getConf('api_key') : false,
            $this->getConf('api_usr'), $this->getConf('api_pwd'), 'array') ;
        $token_file = ($this->getConf('api_taf') ? 'token' : false) ;
        // TITLE
        if ($this->getConf('opo_dat') && $hash_in['hostname']) {
            $_path = 'index.php?page=tools&section=devices&subnetId=' . $hash_in['id'] ;
            $web_out .= "\n\t<h6 class='device_name'>" ;
            if ($_host) {
                $web_out .= $this->setALink("$_host$_path", $hash_in['hostname']) ;
            } else {
                $web_out .= $hash_in['hostname'] ;
            }
            $web_out .= "</h6>" ;
        }
        // HEAD
        if ($show_it <= 0) {
            $_more = array() ;
            $_conf = trim($this->getConf('opo_efd')) ;
            // prepare some specific parts
            if ($hash_in['ip_addr'] && !preg_match('/\bip_addr\b/', $_conf) ) {
                $_more['ip_addr'][1] = $hash_in['ip_addr'] ;
                $_more['ip_addr'][0] = $this->getLang('ip_addr') ;
            }
            if ($hash_in['type'] && !preg_match('/\btype\b/', $_conf) ) {
                $rest_api->execute('GET', 'tools',
                    array('device_types', $hash_in['type']), array(), $token_file) ;
                $reply = $rest_api->get_result() ;
                if (!$reply['data']) {
                    $rest_api->execute('GET', 'tools',
                        array('devicetypes', $hash_in['type']), array(), $token_file) ;
                    $reply = $rest_api->get_result() ;
                }
                if ($reply['data']) {
                    $_info = (array)$reply['data'] ;
                    $_more['type'][1] = '<abbr title="' . hsc($_info['tdescription']) ;
                    $_more['type'][1] .= '">' . hsc($_info['tname']) . '</abbr>' ;
                    $_more['type'][0] = $this->getLang('device_type') ;
                    unset($_info) ;
                } else {
                    $_more['type'][1] = $hash_in['type'] ;
                    $_more['type'][0] = $this->getLang('type') ;
                }
            }
            if ($hash_in['vendor'] && !preg_match('/\bvendor\b/', $_conf) ) {
                $_more['vendor'][1] = hsc($hash_in['vendor']) ;
                $_more['vendor'][0] = $this->getLang('vendor') ;
            }
            if ($hash_in['model'] && !preg_match('/\bmodel\b/', $_conf) ) {
                $_more['model'][1] = hsc($hash_in['model']) ;
                $_more['model'][0] = $this->getLang('model') ;
            }
            if ($hash_in['rack_size'] && !preg_match('/\brack_size\b/', $_conf) ) {
                $_more['rack_size'][1] = $hash_in['rack_size'] ;
                $_more['rack_size'][0] = $this->getLang('rack_size') ;
            }
            if ($hash_in['description'] && !preg_match('/\bdescription\b/', $_conf) ) {
                $_more['description'][1] = hsc($hash_in['description']) ;
                $_more['description'][0] = $this->getLang('description') ;
            }
            // prepare requested parts
            if ($_conf) {
                foreach (explode(',', $_conf) as $_key) {
                    if ($hash_in[$_key]) {
                        $_more[$_key][1] = hsc($hash_in[$_key]) ;
                        $_lang = $this->getLang($_key) ;
                        $_more[$_key][0] = ($_lang ? "$_lang" : ucwords(strtr($_key, '_', ' '))) ;
                    }
                }
            }
            // build the output
            $web_out .= $this->listProps($_more, 'device') ;
            unset($_more, $_conf) ;
        }
        // TAIL
        if ($show_it >= 0) {
            $rest_api->execute('GET', 'tools',
                array('devices', $hash_in['id'], 'addresses'), array(), $token_file) ;
            $reply = $rest_api->get_result() ;
            if (isset($reply['data'])) {
                $web_out .= $this->listAddrs((array)$reply['data']) ;
            } elseif ($this->getConf('allowdebug')) {
                $web_out .= '<p class="level4">' . $reply['message'] . '</p>' ;
            }
        }
        $web_out .= "\n</div>" ;
        return "$web_out" ;
    }

    /**
     * Convert from Decimal to Sexadecimal
     *
     * @param  $dec integer Decimal value to convert from
     * @param  $lat boolean 0 for longitude 1 for latitude
     * @retval $dms string  HTML part for the wiki
     *
     * @note https://www.dougv.com/2012/03/converting-latitude-and-longitude-coordinates-between-decimal-and-degrees-minutes-seconds/
     */
    private function dec2DMS($dec, $lat=false) {
        // set defaults
        $direction = 'X' ;
        $degrees = 0 ;
        $minutes = 0 ;
        $seconds = 0 ;
        // check entries
        if (!is_numeric($dec) || abs($dec) > 180) {
            return '' ;
        }
        // set direction; assume north
        if ($lat && $dec < 0) {
            $direction = 'S' ;
        } elseif (!$lat && $dec < 0) {
            $direction = 'W' ;
        } elseif (!$lat) {
            $direction = 'E' ;
        } else {
            $direction = 'N' ;
        }
        // get absolute value of $dec
        $d = abs($dec) ;
        // get degrees
        $degrees = floor($d) ;
        // get seconds
        $seconds = ($d - $degrees) * 3600 ;
        // get minutes
        $minutes = floor($seconds / 60) ;
        // reset seconds
        $seconds = floor($seconds - ($minutes * 60)) ;
        // return the result
        $dms = "<abbr class='" . ($lat ? 'latitude' : 'longitude') ;
        $dms .= ' ' . ($lat ? 'p-latitude' : 'p-longitude') ;
        $dms .= "' title='$dec'>$degrees&deg;&nbsp;$minutes&apos;&nbsp;$seconds&quot;" ;
        $dms .= "&nbsp;$direction</abbr>" ;
        return "$dms" ;
    }

    /**
     * Display Location and/or linked addresses/subnets
     *
     * @param  $hash_in mixed   IPAM result data value
     * @param  $show_it integer for part to display
     * @param  $alterne boolean for shorter listing
     * @retval $web_out string  HTML part for the wiki
     *
     * @todo code similar to ::showFwd => factorisation
     */
    private function showLoc($hash_in, $show_it, $alternate=false) {
        $web_out = "\n<div class='phpipam phpipam_location-" . $hash_in['id'] . "'>" ;
        $_host = $this->getConf('opo_url') ;
        $rest_api = new phpipam_api_client(
            $this->getConf('api_url'), $this->getConf('api_app'),
            $this->getConf('api_key') ? $this->getConf('api_key') : false,
            $this->getConf('api_usr'), $this->getConf('api_pwd'), 'array') ;
        $token_file = ($this->getConf('api_taf') ? 'token' : false) ;
        // TITLE
        if ($this->getConf('opo_dat') && $hash_in['name']) {
            $_path = 'index.php?page=tools&section=locations&subnetId=' . $hash_in['id'] ;
            $web_out .= "\n\t<h6 class='location_name'>" ;
            if ($_host) {
                $web_out .= $this->setALink("$_host$_path", $hash_in['name']) ;
            } else {
                $web_out .= $hash_in['name'] ;
            }
            $web_out .= "</h6>" ;
        }
        // HEAD
        if ($show_it <= 0) {
            $_more = array() ;
            $_conf = trim($this->getConf('opo_efl')) ;
            // prepare some specific parts
            #if ($hash_in['description'] && !preg_match('/\bdescription\b/', $_conf) ) {
            #    $_more['description'][1] = hsc($hash_in['description']) ;
            #    $_more['description'][0] = $this->getLang('description') ;
            #}
            if ($hash_in['address'] && !preg_match('/\baddress\b/', $_conf) ) {
                // http://microformats.rog/wiki/adr
                // http://microformats.rog/wiki/h-adr
                $_more['address'][1] = "<span class='adr h-adr'>" ;
                $_more['address'][1] .= str_replace(',', '<br />',
                    hsc($hash_in['address'])) . '</span>' ;
                $_more['address'][0] = $this->getLang('address') ;
            }
            if ($hash_in['lat'] && $hash_in['long']) {
                // https://developers.google.com/maps/documentation/urls/guide#map-action
                // https://developers.google.com/maps/documentation/geocoding/best-practices
                // https://developers.google.com/places/place-id
                // https://stackoverflow.com/a/7768890
                // https://github.com/googlei18n/libaddressinput/issues/119
                $_more['link2map'][1] = $this->setALink('https://www.google.com/maps/search/?api=1&query=' . $hash_in['lat'] . ',' . $hash_in['long'] ) ;
                $_more['link2map'][0] = $this->getLang('googlemaps') ;
                // http://microformats.rog/wiki/geo
                // http://microformats.rog/wiki/h-geo
                $_more['link2map'][1] .= '<span class="geo h-geo">' ;
                $_more['link2map'][1] .= $this->dec2DMS($hash_in['lat'], true) ;
                $_more['link2map'][1] .= ' ' . $this->dec2DMS($hash_in['long'], false) ;
                $_more['link2map'][1] .= '</span></a>' ;
            }
            // prepare requested parts
            if ($_conf) {
                foreach (explode(',', $_conf) as $_key) {
                    if ($hash_in[$_key]) {
                        $_more[$_key][1] = hsc($hash_in[$_key]) ;
                        $_lang = $this->getLang($_key) ;
                        $_more[$_key][0] = ($_lang ? "$_lang" : ucwords(strtr($_key, '_', ' '))) ;
                    }
                }
            }
            // build the output
            $web_out .= $this->listProps($_more, 'location') ;
            unset($_more, $_conf) ;
        }
        // TAIL
        if ($show_it >= 0 && !$alternate) {
            $rest_api->execute('GET', 'tools',
                array('locations', $hash_in['id'], 'ipaddresses'), array(), $token_file) ;
            $reply = $rest_api->get_result() ;
            if (isset($reply['data'])) {
                $web_out .= $this->listAddrs((array)$reply['data']) ;
            } elseif ($this->getConf('allowdebug')) {
                $web_out .= '<p class="level4">' . $reply['message'] . '</p>' ;
            }
        }
        if ($show_it >= 0 && $alternate) {
            $rest_api->execute('GET', 'tools',
                array('locations', $hash_in['id'], 'subnets'), array(), $token_file) ;
            $reply = $rest_api->get_result() ;
            if (isset($reply['data'])) {
                $_count = 1 ;
                $_more = array(
                    0 => array('subnet', 'description'),
                ) ;
                foreach ((array)$reply['data'] as $subnet) {
                    $_infos = (array)$subnet ;
                    $_more[$_count][1] = hsc($_infos['description']) ;
                    $_path = 'index.php?page=' ;
                    $_path .= ($_infos['isFolder'] ? 'folders' : 'subnets') ;
                    $_path .= '&section=' . $_infos['sectionId'] ;
                    $_path .= '&subnetId=' . $_infos['id'] ;
                    if ($_infos['isFolder'])
                        $_subnet = $this->getLang('isFolder') ;
                    else
                        $_subnet = $_infos['subnet'] . '/' . $_infos['mask'] ;
                    if ($_host)
                        $_subnet = $this->setALink("$_host$_path", $_subnet) ;
                    $_more[$_count][0] = "$_subnet" ;
                    $_count += 1 ;
                }
                $web_out .= $this->listNets($_more, 'subnet') ;
                unset($_more) ;
            } elseif ($this->getConf('allowdebug')) {
                $web_out .= '<p class="level4">' . $reply['message'] . '</p>' ;
            }
        }
        $web_out .= "\n</div>" ;
        return "$web_out" ;
    }

    /**
     * Display IP Status and/or linked addresses
     *
     * @param  $hash_in mixed   IPAM result data value
     * @param  $show_it integer for part to display
     * @retval $web_out string  HTML part for the wiki
     */
    private function showTag($hash_in, $show_it) {
        $web_out = "\n<div class='phpipam phpipam_tag-" . $hash_in['id'] . "'>" ;
        $_host = $this->getConf('opo_url') ;
        $rest_api = new phpipam_api_client(
            $this->getConf('api_url'), $this->getConf('api_app'),
            $this->getConf('api_key') ? $this->getConf('api_key') : false,
            $this->getConf('api_usr'), $this->getConf('api_pwd'), 'array') ;
        $token_file = ($this->getConf('api_taf') ? 'token' : false) ;
        // TITLE
        if ($this->getConf('opo_dat') && $hash_in['name']) {
            $_path = 'index.php?page=administration&section=tags' ;
            $web_out .= "\n\t<h6 class='tag_name'>" ;
            if ($_host) {
                $web_out .= $this->setALink("$_host$_path", $hash_in['type']) ;
            } else {
                $web_out .= $hash_in['type'] ;
            }
            $web_out .= "</h6>" ;
        }
        // HEAD
        if ($show_it <= 0) {
            $_more = array() ;
            // prepare some specific parts
            foreach (array('showtag', 'compress', 'locked', 'updateTag') as $tag) {
                if ($hash_in["$tag"]) {
                    $_more["$tag"][1] = ( $hash_in["$tag"] ? $this->getLang('yes') : $this->getLang('no') ) ;
                    $_more["$tag"][0] = $this->getLang("$tag") ;
                }
            } 
            foreach (array('fgcolor', 'bgcolor') as $color) {
                if ($hash_in["$color"]) {
                    $_more["$color"][1] = "<div style='background-color:" . $hash_in["$color"] ;
                    $_more["$color"][1] .= "'>" . $hash_in["$color"] . '</div>' ;
                    $_more["$color"][0] = $this->getLang("$color") ;
                }
            } 
            // build the output
            $web_out .= $this->listProps($_more, 'tag') ;
            unset($_more) ;
        }
        // TAIL
        if ($show_it >= 0) {
            $rest_api->execute('GET', 'addresses',
                array('tags', $hash_in['id'], 'addresses'), array(), $token_file) ;
            $reply = $rest_api->get_result() ;
            if (isset($reply['data'])) {
                $web_out .= $this->listAddrs((array)$reply['data']) ;
            } elseif ($this->getConf('allowdebug')) {
                $web_out .= '<p class="level4">' . $reply['message'] . '</p>' ;
            }
        }
        $web_out .= "\n</div>" ;
        return "$web_out" ;
    }

    /**
     * Display networks/subnets list
     *
     * @param  $list mixed  ordered list
     * each entry is: => array("first item", "second item")
     * very first entry (rank zero) holds column names
     * @param  $type string Object type name
     * @retval $html string Web fragment output
     */
    private function listNets($list, $type) {
        if (empty($list)) {
            if ($this->getConf('allowdebug')) {
                return "<p class='level4 warning'>" . "No Network" . '</p>' ;
            } else 
                return '' ;
        }
        $_row = 1 ;
        $_key = $list[0] ;
        switch($this->getConf('opo_lst')) {
            case 'dl' :
                $_css = "width: 29em; float: left; margin: 0; padding: .5em; border-top: 1px solid #999;" ;
                $html = "\n\t<dl class='inline phpipam_${type}s' style='width: 60em; margin: 2em 0; padding: 0;'>" ;
                $html .= "\n\t\t<dt class='row0 phpipam_${type}_$_key[0] col0'" ;
                $html .= " style='$_css font-weight: bold; clear: left;'>" ;
                $html .= $this->getLang($_key[0]) . '</dt>' ;
                $html .= "\n\t\t<dt class='row0 phpipam_${type}_$_key[1] col1'" ;
                $html .= " style='$_css font-weight: bold; clear: right;'>" ;
                $html .= $this->getLang($_key[1]) . '</dt>' ;
                unset($list[0]) ;
                foreach ($list as $_val) {
                    $html .= "\n\t\t<dd class='row$_row phpipam_${type}_$_key[0] col0'" ;
                    $html .= " style='$_css clear: left;'>" . $_val[0] . '</dd>' ;
                    $html .= "\n\t\t<dd class='row$_row phpipam_${type}_$_key[1] col1'" ;
                    $html .= " style='$_css clear: right;'>" . $_val[1] . '</dd>' ;
                    $_row += 1 ;
                }
                $html .= "\n\t</dl>" ;
                break ;
            case 'tr' :
                $html = "\n\t<table class='inline phpipam_${type}s'>\n\t<thead>" ;
                $html .= "\n\t\t<tr class='row0'>" ;
                $html .= "\n\t\t\t<th class='col0 phpipam_${type}_$key[0]'>" ;
                $html .= $this->getLang($_key[0]) . '</th>' ;
                $html .= "\n\t\t\t<th class='col1 phpipam_${type}_$key[1]'>" ;
                $html .= $this->getLang($_key[1]) . '</th>' ;
                $html .= "\n\t\t</tr>\n\t</thead>\n\t<tbody>" ;
                unset($list[0]) ;
                foreach ($list as $_val) {
                    $html .= "\n\t\t<tr class='row$_row'>" ;
                    $html .= "\n\t\t\t<td class='col0 phpipam_${type}_$key[0]'>" ;
                    $html .= $_val[0] . '</td>' ;
                    $html .= "\n\t\t\t<td class='col1 phpipam_${type}_$key[1]'>" ;
                    $html .= $_val[1] . '</td>' ;
                    $html .= "\n\t\t</tr>" ;
                    $_row += 1 ;
                }
                $html .= "\n\t</tbody>\n\t</table>" ;
                break ;
            case 'ul' :
                $html = "\n\t<ul class='phpipam_${type}s'>" ;
                $html .= "\n\t\t<li class='level3 row0'>" ;
                $html .= "\n\t\t\t<div class='li'>" ;
                $html .= "<b class='col0 phpipam_${type}_$_key[0]'>" ;
                $html .= $this->getLang($_key[0]) . '</b>' ;
                $html .= ' &#8596; ' ;
                $html .= "<b class='col1 phpipam_${type}_$_key[1]'>" ;
                $html .= $this->getLang($_key[1]) . '</b>' ;
                $html .= "\n\t\t\t</div>" ;
                $html .= "\n\t\t</li>" ;
                unset($list[0]) ;
                foreach ($list as $_key => $_val) {
                    $html .= "\n\t\t<li class='level3 row$_row'>" ;
                    $html .= "\n\t\t\t<div class='li'>" ;
                    $html .= "<span class='col0 phpipam_${type}_$_key[0]'>" ;
                    $html .= $_val[0] . '</span>' ;
                    $html .= ' &#8596; ' ;
                    $html .= "<span class='col1 phpipam_${type}_$_key[1]'>" ;
                    $html .= $_val[1] . '</span>' ;
                    $html .= "\n\t\t\t</div>" ;
                    $html .= "\n\t\t</li>" ;
                    $_row += 1 ;
                }
                $html .= "\n\t</ul>" ;
                break ;
            case 'p' :
                $html = "\n\t<div class='phpipam_${type}s'>" ;
                $html .= "\n\t\t<p class='level3 row0'>" ;
                $html .= "<b class='col0 phpipam_${type}_$_key[0]'>" ;
                $html .= $this->getLang($_key[0]) . '</b>' ;
                $html .= ' &#8596; ' ;
                $html .= "<b class='col1 phpipam_${type}_$_key[1]'>" ;
                $html .= $this->getLang($_key[1]) . '</b>' ;
                $html .= "\n\t\t</p>" ;
                unset($list[0]) ;
                foreach ($list as $_val) {
                    $html .= "\n\t\t<p class='level3 row$_row'>" ;
                    $html .= "<span class='col0 phpipam_${type}_$_key[0]'>" ;
                    $html .= $_val[0] . '</span>' ;
                    $html .= ' &#8596; ' ;
                    $html .= "<span class='col1 phpipam_${type}_$_key[1]'>" ;
                    $html .= $_val[1] . '</span>' ;
                    $html .= "\n\t\t</p>" ;
                    $_row += 1 ;
                }
                $html .= "\n\t</div>" ;
                break ;
            case 'pre' :
                $html = "\n\t<pre class='code text phpipam_${type}s'>" ;
                $html .= "<span class='row0'>" ;
                $html .= "<b class='col0 phpipam_${type}_$_key[0]'>" ;
                $html .= $this->getLang($_key[0]) . '</b>' ;
                $html .= "\t<b class='col1 phpipam_${type}_$_key[1]'>" ;
                $html .= $this->getLang($_key[1]) . '</b>' ;
                $html .= "</span>\n" ;
                unset($list[0]) ;
                foreach ($list as $_val) {
                    $html .= "<span class='row$_row'>" ;
                    $html .= "<span class='col0 phpipam_${type}_$_key[0]'>" ;
                    $html .= $_val[0] . '</span>' ;
                    $html .= "\t<span class='col1 phpipam_${type}_$_key[1]'>" ;
                    $html .= $_val[1] . '</span>' ;
                    $html .= "</span>\n" ;
                    $_row += 1 ;
                }
                $html .= "</pre>\n" ;
                break ;
        }
        unset($_row) ;
        return "$html" ;
    }

    /**
     * Display layer 2 Network and/or linked subnetworks
     *
     * @param  $hash_in mixed   IPAM result data value
     * @param  $show_it integer for part to display
     * @retval $web_out string  HTML part for the wiki
     */
    private function showNet($hash_in, $show_it) {
        $web_out = "\n<div class='phpipam phpipam_vlan-" . $hash_in['id'] . "'>" ;
        $_host = $this->getConf('opo_url') ;
        $rest_api = new phpipam_api_client(
            $this->getConf('api_url'), $this->getConf('api_app'),
            $this->getConf('api_key') ? $this->getConf('api_key') : false,
            $this->getConf('api_usr'), $this->getConf('api_pwd'), 'array') ;
        $token_file = ($this->getConf('api_taf') ? 'token' : false) ;
        // TITLE
        if ($this->getConf('opo_dat') && $hash_in['name']) {
            $_path = 'index.php?page=tools&section=vlan' ;
            $_path .= '&subnetId=' . $hash_in['domainId'] ;
            $_path .= '&sPage=' . $hash_in['id'] ;
            $web_out .= "\n\t<h6 class='vlan_name'>" ;
            if ($_host)
                $web_out .= $this->setALink("$_host$_path") ;
            $web_out .= $this->getLang('vlan') . ' ' . $hash_in['number'] ;
            if ($_host)
                $web_out .= '</a>' ;
            $web_out .= "</h6>" ;
        }
        // HEAD
        if ($show_it <= 0) {
            $_more = array() ;
            $_conf = trim($this->getConf('opo_efv')) ;
            // prepare some specific parts
            if ($hash_in['name'] && !preg_match('/\bname\b/', $_conf) ) {
                $_more['name'][1] = hsc($hash_in['name']) ;
                $_more['name'][0] = $this->getLang('name') ;
            }
            if ($hash_in['description'] && !preg_match('/\bdescription\b/', $_conf) ) {
                $_more['description'][1] = hsc($hash_in['description']) ;
                $_more['description'][0] = $this->getLang('description') ;
            }
            /*
            if ($hash_in['domainId'] && !preg_match('/\bdomainId\b/', $_conf) ) {
                $rest_api->execute('GET', 'l2domains',
                    array($hash_in['domainId']), array(), $token_file) ;
                $reply = $rest_api->get_result() ;
                if ($reply['data']) {
                    $_info = (array)$reply['data'] ;
                    $_more['domainId'][1] = '<abbr title="' . hsc($_info['description']) ;
                    $_more['domainId'][1] .= '">' . hsc($_info['name']) . '</abbr>' ;
                    $_more['domainId'][0] = $this->getLang('l2domain') ;
                    unset($_info) ;
                }
            }
            */
            // prepare requested parts
            if ($_conf) {
                foreach (explode(',', $_conf) as $_key) {
                    if ($hash_in[$_key]) {
                        $_more[$_key][1] = hsc($hash_in[$_key]) ;
                        $_lang = $this->getLang($_key) ;
                        $_more[$_key][0] = ($_lang ? "$_lang" : ucwords(strtr($_key, '_', ' '))) ;
                    }
                }
            }
            // build the output
            $web_out .= $this->listProps($_more, 'vlan') ;
            unset($_more, $_conf) ;
        }
        // TAIL
        if ($show_it >= 0) {
            $rest_api->execute('GET', 'vlans',
                array($hash_in['id'], 'subnets'), array(), $token_file) ;
            $reply = $rest_api->get_result() ;
            if (isset($reply['data'])) {
                $_count = 1 ;
                $_more = array(
                    0 => array('subnet', 'description'),
                ) ;
                foreach ((array)$reply['data'] as $subnet) {
                    $_infos = (array)$subnet ;
                    $_more[$_count][1] = hsc($_infos['description']) ;
                    $_path = 'index.php?page=' ;
                    $_path .= ($hash_in['isFolder'] ? 'folders' : 'subnets') ;
                    $_path .= '&section=' . $hash_in['sectionId'] ;
                    $_path .= '&subnetId=' . $hash_in['id'] ;
                    if ($_infos['isFolder'])
                        $_subnet = $this->getLang('isFolder') ;
                    else
                        $_subnet = $_infos['subnet'] . '/' . $_infos['mask'] ;
                    if ($_host)
                        $_subnet = $this->setALink("$_host$_path", $_subnet) ;
                    $_more[$_count][0] = "$_subnet" ;
                    $_count += 1 ;
                }
                $web_out .= $this->listNets($_more, 'subnet') ;
                unset($_more) ;
            } elseif ($this->getConf('allowdebug')) {
                $web_out .= '<p class="level4">' . $reply['message'] . '</p>' ;
            }
        }
        $web_out .= "\n</div>" ;
        return "$web_out" ;
    }

    /**
     * Display layer 2 Domain and/or linked virt. networks
     *
     * @param  $hash_in mixed   IPAM result data value
     * @param  $show_it integer for part to display
     * @retval $web_out string  HTML part for the wiki
     */
    private function showDom($hash_in, $show_it) {
        $web_out = "\n<div class='phpipam phpipam_l2domain-" . $hash_in['id'] . "'>" ;
        $_host = $this->getConf('opo_url') ;
        $rest_api = new phpipam_api_client(
            $this->getConf('api_url'), $this->getConf('api_app'),
            $this->getConf('api_key') ? $this->getConf('api_key') : false,
            $this->getConf('api_usr'), $this->getConf('api_pwd'), 'array') ;
        $token_file = ($this->getConf('api_taf') ? 'token' : false) ;
        // TITLE
        if ($this->getConf('opo_dat') && $hash_in['name']) {
            $_path = 'index.php?page=tools&section=vlan' ;
            $_path .= '&subnetId=' . $hash_in['id'] ;
            $web_out .= "\n\t<h6 class='vlan_name'>" ;
            if ($_host) {
                $web_out .= $this->setALink("$_host$_path", $hash_in['name']) ;
            } else {
                $web_out .= $hash_in['name'] ;
            }
            $web_out .= "</h6>" ;
        }
        // HEAD
        if ($show_it <= 0) {
            $_more = array() ;
            $_conf = trim($this->getConf('opo_ef2')) ;
            // prepare some specific parts
            if ($hash_in['description'] && !preg_match('/\bdescription\b/', $_conf) ) {
                $_more['description'][1] = hsc($hash_in['description']) ;
                $_more['description'][0] = $this->getLang('description') ;
            }
            // prepare requested parts
            if ($_conf) {
                foreach (explode(',', $_conf) as $_key) {
                    if ($hash_in[$_key]) {
                        $_more[$_key][1] = hsc($hash_in[$_key]) ;
                        $_lang = $this->getLang($_key) ;
                        $_more[$_key][0] = ($_lang ? "$_lang" : ucwords(strtr($_key, '_', ' '))) ;
                    }
                }
            }
            // build the output
            $web_out .= $this->listProps($_more, 'l2domain') ;
            unset($_more, $_conf) ;
        }
        // TAIL
        if ($show_it >= 0) {
            $rest_api->execute('GET', 'l2domains',
                array($hash_in['id'], 'vlans'), array(), $token_file) ;
            $reply = $rest_api->get_result() ;
            if (isset($reply['data'])) {
                $_count = 1 ;
                $_more = array(
                    0 => array('number', 'name'),
                ) ;
                foreach ((array)$reply['data'] as $vlan) {
                    $_infos = (array)$vlan ;
                    $_more[$_count][1] = '<abbr title="' ;
                    $_more[$_count][1] = hsc($_infos['description']) . '">' ;
                    $_more[$_count][1] = hsc($_infos['name']) . '</abbr>' ;
                    $_path = 'index.php?page=tools&section=vlan' ;
                    $_path .= '&subnetId=' . $hash_in['id'] ;
                    $_path .= '&sPage=' . $_infos['id'] ;
                    $_network = $_infos['number'] ;
                    if ($_host)
                        $_network = $this->setALink("$_host$_path", $_network) ;
                    $_more[$_count][0] = "$_network" ;
                    $_count += 1 ;
                }
                $web_out .= $this->listNets($_more, 'vlan') ;
                unset($_more) ;
            } elseif ($this->getConf('allowdebug')) {
                $web_out .= '<p class="level4">' . $reply['message'] . '</p>' ;
            }
        }
        $web_out .= "\n</div>" ;
        return "$web_out" ;
    }

    /**
     * Display virt. routing and/or linked addresses/subnets
     *
     * @param  $hash_in mixed   IPAM result data value
     * @param  $show_it integer for part to display
     * @param  $alterne boolean for shorter listing
     * @retval $web_out string  HTML part for the wiki
     *
     * @todo code similar to ::showLoc => factorisation
     */
    private function showFwd($hash_in, $show_it, $alternate=false) {
        $web_out = "\n<div class='phpipam phpipam_vrf-" . $hash_in['id'] ;
        foreach (explode(',', $hash_in['sections']) as $_section) {
            $web_out .= " phpipam_section-" . $_section ;
        }
        $web_out .= "'>" ;
        $_host = $this->getConf('opo_url') ;
        $rest_api = new phpipam_api_client(
            $this->getConf('api_url'), $this->getConf('api_app'),
            $this->getConf('api_key') ? $this->getConf('api_key') : false,
            $this->getConf('api_usr'), $this->getConf('api_pwd'), 'array') ;
        $token_file = ($this->getConf('api_taf') ? 'token' : false) ;
        // TITLE
        if ($this->getConf('opo_dat') && $hash_in['name']) {
            $_path = 'index.php?page=tools&section=vrf&subnetId=' . $hash_in['id'] ;
            $web_out .= "\n\t<h6 class='vrf_name'>" ;
            if ($_host) {
                $web_out .= $this->setALink("$_host$_path", $hash_in['name']) ;
            } else {
                $web_out .= $hash_in['name'] ;
            }
            $web_out .= "</h6>" ;
        }
        // HEAD
        if ($show_it <= 0) {
            $_more = array() ;
            $_conf = trim($this->getConf('opo_efr')) ;
            // prepare some specific parts
            if ($hash_in['rd'] && !preg_match('/\brd\b/', $_conf) ) {
                $_more['rd'][1] = hsc($hash_in['rd']) ;
                $_more['rd'][0] = $this->getLang('rd') ;
            }
            if ($hash_in['description'] && !preg_match('/\bdescription\b/', $_conf) ) {
                $_more['description'][1] = hsc($hash_in['description']) ;
                $_more['description'][0] = $this->getLang('description') ;
            }
            // prepare requested parts
            if ($_conf) {
                foreach (explode(',', $_conf) as $_key) {
                    if ($hash_in[$_key]) {
                        $_more[$_key][1] = hsc($hash_in[$_key]) ;
                        $_lang = $this->getLang($_key) ;
                        $_more[$_key][0] = ($_lang ? "$_lang" : ucwords(strtr($_key, '_', ' '))) ;
                    }
                }
            }
            // build the output
            $web_out .= $this->listProps($_more, 'vrf') ;
            unset($_more, $_conf) ;
        }
        // TAIL
        if ($show_it >= 0 && !$alternate) {
            $rest_api->execute('GET', 'vrf',
                array($hash_in['id'], 'subnets'), array(), $token_file) ;
            $reply = $rest_api->get_result() ;
            if (isset($reply['data'])) {
                $web_out .= $this->listAddrs((array)$reply['data']) ;
            } elseif ($this->getConf('allowdebug')) {
                $web_out .= '<p class="level4">' . $reply['message'] . '</p>' ;
            }
        }
        if ($show_it >= 0 && $alternate) {
            $rest_api->execute('GET', 'vrf',
                array($hash_in['id'], 'subnets'), array(), $token_file) ;
            $reply = $rest_api->get_result() ;
            if (isset($reply['data'])) {
                $_count = 1 ;
                $_more = array(
                    0 => array('subnet', 'description'),
                ) ;
                foreach ((array)$reply['data'] as $subnet) {
                    $_infos = (array)$subnet ;
                    $_more[$_count][1] = hsc($_infos['description']) ;
                    $_path = 'index.php?page=' ;
                    $_path .= ($hash_in['isFolder'] ? 'folders' : 'subnets') ;
                    $_path .= '&section=' . $hash_in['sectionId'] ;
                    $_path .= '&subnetId=' . $hash_in['id'] ;
                    if ($_infos['isFolder'])
                        $_subnet = $this->getLang('isFolder') ;
                    else
                        $_subnet = $_infos['subnet'] . '/' . $_infos['mask'] ;
                    if ($_host)
                        $_subnet = $this->setALink("$_host$_path", $_subnet) ;
                    $_more[$_count][0] = "$_subnet" ;
                    $_count += 1 ;
                }
                $web_out .= $this->listNets($_more, 'subnet') ;
                unset($_more) ;
            } elseif ($this->getConf('allowdebug')) {
                $web_out .= '<p class="level4">' . $reply['message'] . '</p>' ;
            }
        }
        $web_out .= "\n</div>" ;
        return "$web_out" ;
    }

    /**
     * Display Devices list
     *
     * @param  $dataList mixed   list of devices indexed by position
     * @param  $rackInfo mixed   informations about the cabinet
     * @param  $rackFrom integer rack U starting/floor position 
     * @retval $web_out  string  HTML table for the wiki page
     */
    private function listRacks($dataList, $rackInfo, $rackFrom = 1) {
        $web_out = "\n\t<table class='phpipam_devices'>" ;
        $web_out .= "\n\t\t<tr><th colspan='2' class='title rack_name'" ;
        #$web_out .= "\n\t<caption" ;
        $web_out .= " title='" .  $rackInfo['description'] . "'>" ;
        $web_out .= '['. ($rackFrom <= $rackInfo['size'] ? $this->getLang('front') : $this->getLang('back')) . '] ' ;
        $web_out .= $rackInfo['name'] . '</th></tr>' ;
        #$web_out .= $rackInfo['name'] . '</caption>' ;
        for ($_u = $rackInfo['size']+$rackFrom-1; $_u >= $rackFrom; $_u -= 1) {
            #if ($dataList[$_u] && $dataList[$_u]['model']) {
            if ($dataList[$_u]) {
                $item = $dataList[$_u] ; 
                $web_out .= "\n\t\t<tr class='phpipam_device phpipam_device_type-" . $item['class'] ; 
                $web_out .= "'></th><th>" . ($_u - $rackFrom + 1) . '</th>' ; 
                $web_out .= "\n\t\t\t<td class='item' rowspan='" . $item['u_size'] ;
                if ($this->getConf('opo_eca')) 
                    $web_out .= "' style='background-color: " . $item['color'] . ";'" ;
                else
                    $web_out .= "'" ;
                $web_out .= ' title="' . hsc($item['comment']) . '">' ;
                if ($item['link']) 
                    $web_out .= "<a href='" . $item['link'] . "' " . $item['linktitle'] . '>' ;
                $web_out .= "<div style='float: left; font-weight: bold;'>" ;
                $web_out .= $item['model'] . ($item['comment'] ? ' *' : '') ;
                $web_out .= "</div><div style='float: right; margin-left: 3em;'>" ;
                $web_out .= $item['name'] . '</div>' ;
                if ($item['link']) 
                    $web_out .= "</a>" ;
                $web_out .= '</td></tr>' ;
                for ($_d = 1; $_d < $item['u_size']; $_d++ ) {
                    $_u -= 1 ;
                    $web_out .= "\n\t\t<tr class='phpipam_device phpipam_device_type-" . $item['class'] ;
                    $web_out .= "'><th>" . ($_u - $rackFrom + 1) . '</th></tr>' ;
                }
            } else {
                $web_out .= "\n\t\t<tr class'phpipam_device'><th>" . ($_u - $rackFrom + 1) . "</th><td class='empty'></td></tr>" ;
            }
        }
        $web_out .= "\n\t</table>" ;
        return "$web_out" ;
    }

    /**
     * Display enclosed devices in a Cabinet
     *
     * @param  $hash_in mixed   IPAM result data value
     * @param  $show_it integer for part to display
     * @retval $web_out string  HTML part for the wiki
     */
    private function showCab($hash_in, $show_it) {
        // prepare rows
        $rest_api = new phpipam_api_client(
            $this->getConf('api_url'), $this->getConf('api_app'),
            $this->getConf('api_key') ? $this->getConf('api_key') : false,
            $this->getConf('api_usr'), $this->getConf('api_pwd'), 'array') ;
        $token_file = ($this->getConf('api_taf') ? 'token' : false) ;
        $rest_api->execute('GET', 'tools',
            array('racks', $hash_in['id'], 'devices'), array(), $token_file) ;
        $reply = $rest_api->get_result() ;
        $items = array() ;
        foreach ((array)$reply['data'] as $device) {
            $line = (array)$device ;
            $item['u_bottom'] = $line['rack_start'] ;
            $item['u_size'] = $line['rack_size'] ;
            $item['model'] = $line['vendor'] . ' ' . $line['model'] ;
            $item['name'] = $line['hostname'] ;
            $item['comment'] = $line['description'] ;
            $item['u_top'] = $line['rack_start'] + $line['rack_size'] - 1 ;
            $item['class'] = $line['type'] ;
            $item['link'] = '' ;
            $_l = $this->getConf['opo_cfd'] ;
            if ($_l && $item[$_l]) {
                $item['link'] = $item[$_l] ;
                if (preg_match('/^\[\[[^|]+\|([^]]+)]]$/', $item['link'], $_m)) {
                    $item['linktitle'] = " title='" . hsc($_m[1]) . "'" ;
                }
                $item['link'] = wl(cleanID(preg_replace('/^\[\[([^]|]+).*/',
                    '$1', $item['link']))) ;
            }
            if (preg_match('/(wire|cable)\s*guide|pdu|patch|term|lcd/i',
                $item['model'])) {
                $item['color'] = '#bba' ;
            } elseif (preg_match('/blank/i',
                $item['model'])) {
                $item['color'] = '#fff' ;
            } elseif (preg_match('/netapp|fas\d/i',
                $item['model'])) {
                $item['color'] = '#07c' ;
            } elseif (preg_match('/^sh(elf)?\s/i',
                $item['model'])) {
                $item['color'] = '#0ae' ;
            } elseif (preg_match('/cisco|catalyst|nexus/i',
                $item['model'])) {
                $item['color'] = '#f80' ;
            } elseif (preg_match('/brocade|mds/i',
                $item['model'])) {
                $item['color'] = '#8f0' ;
            } elseif (preg_match('/ucs/i',
                $item['model'])) {
                $item['color'] = '#c00' ;
            } elseif (preg_match('/ibm/i',
                $item['model'])) {
                $item['color'] = '#67a' ;
            } elseif (preg_match('/h ?p/i',
                $item['model'])) {
                $item['color'] = '#a67' ;
            } elseif (preg_match('/dell|emc/i',
                $item['model'])) {
                $item['color'] = '#999' ;
            } else {
                $item['color'] = '#888' ;
            }
            $items[$item['u_top']] = $item ;
        }
        unset ($reply) ;
        $web_out = "\n<div class='phpipam phpipam_rack-" ;
        $web_out .= $hash_in['id'] . "'>" ;
        // build tables
        if ($show_it <= 0) { // front
            $web_out .= $this->listRacks($items, $hash_in, 1) ;
        }
        if ($show_it >= 0 && $hash_in['hasBack'] ) { // back
            $web_out .= $this->listRacks($items, $hash_in, $hash_in['size']+1) ;
        }
        // This JS hack sets the CSS "display" property of the table to "inline",
        // since IE is too dumb ot have heard of the "inline-table" mode..!
        $web_out .= "\n\t<script type='text/javascript'>phpipam_ie6fix()</script>\n" ;
        $web_out .= "\n</div>" ;
        return "$web_out" ;
    }

}

// ex: se ai et ts=4 st=4 bf :
// vi: se ai et ts=4 st=4 bf :
// vim: set ai et ts=4 st=4 bf sts=4 cin ff=unix fenc=utf-8 foldmethod=indent : enc=utf-8
// atom:set useSoftTabs tabLength=4 lineending=lf encoding=utf-8
// -*- Mode: tab-width: 4; c-basic-offset: 4; indent-tabs-mode: nil -*-
?>
