/* A polyfill for browsers that don't support ligatures. */
/* The script tag referring to this file must be placed before the ending body tag. */

/* To provide support for elements dynamically added, this script adds
   method 'icomoonLiga' to the window object. You can pass element references to this method.
*/
(function () {
    'use strict';
    function supportsProperty(p) {
        var prefixes = ['Webkit', 'Moz', 'O', 'ms'],
            i,
            div = document.createElement('div'),
            ret = p in div.style;
        if (!ret) {
            p = p.charAt(0).toUpperCase() + p.substr(1);
            for (i = 0; i < prefixes.length; i += 1) {
                ret = prefixes[i] + p in div.style;
                if (ret) {
                    break;
                }
            }
        }
        return ret;
    }
    var icons;
    if (!supportsProperty('fontFeatureSettings')) {
        icons = {
            'stack': '&#xe948;',
            'layers': '&#xe948;',
            'page-break': '&#xea68;',
            'wysiwyg10': '&#xea68;',
            'table2': '&#xea71;',
            'wysiwyg19': '&#xea71;',
            'superscript': '&#xea69;',
            'wysiwyg11': '&#xea69;',
            'subscript': '&#xea6a;',
            'wysiwyg12': '&#xea6a;',
            'superscript2': '&#xea6b;',
            'wysiwyg13': '&#xea6b;',
            'subscript2': '&#xea6c;',
            'wysiwyg14': '&#xea6c;',
            'embed2': '&#xea80;',
            'code2': '&#xea80;',
            'file-text': '&#xe92e;',
            'file': '&#xe92e;',
            'bold': '&#xea62;',
            'wysiwyg4': '&#xea62;',
            'underline': '&#xea63;',
            'wysiwyg5': '&#xea63;',
            'italic': '&#xea64;',
            'wysiwyg6': '&#xea64;',
            'strikethrough': '&#xea65;',
            'wysiwyg7': '&#xea65;',
            'pilcrow': '&#xea73;',
            'wysiwyg21': '&#xea73;',
            'clipboard': '&#xe9b8;',
            'board': '&#xe9b8;',
            'eyedropper': '&#xe946;',
            'color': '&#xe946;',
            'pen': '&#xe941;',
            'write3': '&#xe941;',
            'share2': '&#xea82;',
            'social': '&#xea82;',
            'windows8': '&#xeac2;',
            'brand57': '&#xeac2;',
            'android': '&#xeac0;',
            'brand55': '&#xeac0;',
            'finder': '&#xeabf;',
            'brand54': '&#xeabf;',
            'apple': '&#xeabe;',
            'brand53': '&#xeabe;',
            'tux': '&#xeabd;',
            'brand52': '&#xeabd;',
            'linkedin2': '&#xeaca;',
            'brand65': '&#xeaca;',
            'pinterest': '&#xead1;',
            'brand72': '&#xead1;',
            'tumblr': '&#xeab9;',
            'brand49': '&#xeab9;',
            'behance': '&#xeaa8;',
            'brand32': '&#xeaa8;',
            'soundcloud': '&#xeac3;',
            'brand58': '&#xeac3;',
            'flickr': '&#xeaa3;',
            'brand27': '&#xeaa3;',
            'lastfm': '&#xeacb;',
            'brand66': '&#xeacb;',
          '0': 0
        };
        delete icons['0'];
        window.icomoonLiga = function (els) {
            var classes,
                el,
                i,
                innerHTML,
                key;
            els = els || document.getElementsByTagName('*');
            if (!els.length) {
                els = [els];
            }
            for (i = 0; ; i += 1) {
                el = els[i];
                if (!el) {
                    break;
                }
                classes = el.className;
                if (/elzIc/.test(classes)) {
                    innerHTML = el.innerHTML;
                    if (innerHTML && innerHTML.length > 1) {
                        for (key in icons) {
                            if (icons.hasOwnProperty(key)) {
                                innerHTML = innerHTML.replace(new RegExp(key, 'g'), icons[key]);
                            }
                        }
                        el.innerHTML = innerHTML;
                    }
                }
            }
        };
        window.icomoonLiga();
    }
}());
