/* IN links navigation, when we're on one of the pages in the navbar, that element gets a special style,
 * Use this script to identify that we're in such a page */
var hashmal_current_url = window.location.href; // get the current location so we can comapre it with the bavigation links

var hashmalLinksNavigation = document.getElementById("hashmal-links-navigation"); // the UL
var hashmalElementChildren = hashmalLinksNavigation.children; // All its LI's

// loop thru LI's and examine their a's. whichever one matches the current location, its parent LI gets the "here" class
for (var i = 0; i < hashmalElementChildren.length; i++) {
    var hashmalElementGrandChildren = hashmalElementChildren[i].children; // The A element in each LI
    if (hashmalElementGrandChildren.length > 0 && hashmalElementGrandChildren[0].hasAttribute("href")) { // make sure it exsists before using it
        if (hashmalElementGrandChildren[0].href === hashmal_current_url) {
            hashmalElementChildren[i].className += " here";
        }
    }
}

/* when sidebar is empty, widen then main section to the whole width */
var hashmalAside = document.getElementById("block-region-side-pre");
if (hashmalAside.children.length == 0) {
    var hashmalMainBox = document.getElementById("region-main");
    // remove the "span9" class or whatever class there is, and give the span12 class
    hashmalMainBox.className = hashmalRemoveClassNameContaining(hashmalMainBox.className, "span") + " span12";
    //remove the "span8" class from region-main
    //document.getElementById("region-main").className = "";
}

function hashmalRemoveClassNameContaining(nodeClass, str) {
    var classPos = nodeClass.indexOf(str);
    if (classPos > -1) {
        var spacePos = nodeClass.indexOf(" ", classPos);
        if (spacePos > -1) {
            nodeClass = nodeClass.substring(0, classPos) + nodeClass.substring(spacePos);
        }
        else { // this is the only class
            nodeClass = "";
        }
    }
    return nodeClass;
}