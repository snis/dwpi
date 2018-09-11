/* function renamed with phpipam prefix
 * to avoid collision with a dokuwiki builtin
 */
function phpipam_getElementsByClass(searchClass,node,tag) {
    var classElements = new Array();
    if ( node == null )
        node = document;
    if ( tag == null )
        tag = '*';
    var els = node.getElementsByTagName(tag);
    var elsLen = els.length;
    var pattern = new RegExp("(^|\\\\s)"+searchClass+"(\\\\s|\$)");
    for (i = 0, j = 0; i < elsLen; i++) {
        if ( pattern.test(els[i].className) ) {
            classElements[j] = els[i];
            j++;
        }
    }
    return classElements;
}

/* IE can't do "display:inline-table",
 * but "inline" works, so we fix this client-side
 */
function phpipam_ie6fix() {
    //alert(navigator.userAgent);
    if(/MSIE/.test(navigator.userAgent)) {
        var tables = phpipam_getElementsByClass('rack');
        for (var i=0; i<tables.length; i++) {
            //alert(i);
            tables[i].style.display = "inline";
        }
    }
}

function phpipam_toggle_vis(element,vis_mode) {
    element.style.display = phpipam_toggle(element.style.display,"none",vis_mode);
    return element.style.display!="none";
}

function phpipam_toggle(v,a,b) {
    return (v==a)?b:a;
}

// ex: se ai et ts=4 st=4 bf :
// vi: se ai et ts=4 st=4 bf :
// vim: set ai et ts=4 st=4 bf sts=4 cin ff=unix fenc=utf-8 foldmethod=indent : enc=utf-8
// atom:set useSoftTabs tabLength=4 lineending=lf encoding=utf-8
// -*- Mode: tab-width: 4; c-basic-offset: 4; indent-tabs-mode: nil -*-
