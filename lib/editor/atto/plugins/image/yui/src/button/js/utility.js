// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @package    atto_image
 * @copyright  2015 Joey Andres <jandres@ualberta.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This is where utility functions are placed so they can be modified easily.
 *
 * @module moodle-atto_image-utility
 */

Y.M.atto_image.utility = {
    /**
     * A helper function for parsing string to base 10 and avoiding jsling/shifter complains about having no radix.
     *
     * @param {String|Number} val
     * @returns {Number}
     */
    parseInt10: function(val) {
        return parseInt(val, 10);
    },

    /**
     * A helper function for getting the natural image size prior to any html attributes and css styling.
     *
     * @param {string} src Source of the image.
     * @return {{width: {Number}, height: {Number}}} The object containing width and height.
     */
    getNaturalImageSize: function(src) {
        var img = new Image();
        img.src = src;
        return {width: img.width, height: img.height};
    },

    /**
     * Acquires the DOM node size without the border and margin.
     *
     * @param {Y.Node} node The to acquire size.
     * @return {{width: {Number}, height: {Number}}} Width and height without the border and margin.
     */
    getNodeSize: function(node) {
        var newWidth =
            Y.M.atto_image.utility.parseInt10(node.getComputedStyle("width")) -
            Y.M.atto_image.utility.getHorizontalNonContentWidth(node);
        var newHeight =
            Y.M.atto_image.utility.parseInt10(node.getComputedStyle("height")) -
            Y.M.atto_image.utility.getVerticalNonContentWidth(node);
        return {width: newWidth, height: newHeight};
    },

    /**
     * A helper function for getting the approximate aspect ratio.
     *
     * @param {{width: {Number}, height: {Number}}} size of the image to acquire aspect ratio of.
     * @returns {number} aspect ratio approximation.
     */
    getNaturalImageAspectRatio: function(size) {
        // We need imageSizeMultiplier so that when we divide, we get more precision for our aspect ratio approximation.
        var imageSizeMultiplier = 1000;
        return (size.width * imageSizeMultiplier) / (size.height * imageSizeMultiplier);
    },

    /**
     * @param {Y.Node} node to acquire the total horizontal border.
     * @returns {Number} Total horizontal border in px.
     */
    getHorizontalBorderWidth: function(node) {
        var borderLeftWidth = Y.M.atto_image.utility.parseInt10(node.getComputedStyle("border-left-width"));
        var borderRightWidth = Y.M.atto_image.utility.parseInt10(node.getComputedStyle("border-right-width"));
        return borderLeftWidth + borderRightWidth;
    },

    /**
     * @param {Y.Node} node to acquire the total vertical border.
     * @returns {Number} Total vertical border in px.
     */
    getVerticalBorderWidth: function(node) {
        var borderTopWidth = Y.M.atto_image.utility.parseInt10(node.getComputedStyle("border-top-width"));
        var borderBottomWidth = Y.M.atto_image.utility.parseInt10(node.getComputedStyle("border-bottom-width"));
        return borderTopWidth + borderBottomWidth;

    },

    /**
     * @param {Y.Node} node to acquire the total horizontal padding.
     * @returns {Number} Total horizontal border in px.
     */
    getHorizontalPaddingWidth: function(node) {
        var paddingLeft = Y.M.atto_image.utility.parseInt10(node.getComputedStyle("padding-left"));
        var paddingRight = Y.M.atto_image.utility.parseInt10(node.getComputedStyle("padding-right"));
        return paddingLeft + paddingRight;
    },

    /**
     * @param {Y.Node} node to acquire the total vertical padding.
     * @returns {Number} Total vertical border in px.
     */
    getVerticalPaddingWidth: function(node) {
        var paddingBottom = Y.M.atto_image.utility.parseInt10(node.getComputedStyle("padding-bottom"));
        var paddingTop = Y.M.atto_image.utility.parseInt10(node.getComputedStyle("padding-top"));
        return paddingBottom + paddingTop;
    },

    /**
     * @param {Y.Node} node to acquire the total non-content (border+padding) width .
     * @returns {Number} Total horizontal non-content in px.
     *
     * Note: Margin is not part of this, since by def'n, margin is outside box-model.
     */
    getHorizontalNonContentWidth: function(node) {
        return this.getHorizontalBorderWidth(node) + this.getHorizontalPaddingWidth(node);
    },

    /**
     * @param {Y.Node} node to acquire the total non-content (border+padding) height.
     * @returns {Number} Total vertical non-content in px.
     *
     * Note: Margin is not part of this, since by def'n, margin is outside box-model.
     */
    getVerticalNonContentWidth: function(node) {
        return this.getVerticalBorderWidth(node) + this.getVerticalPaddingWidth(node);
    },

    /**
     * Compares two rangy object if their selection is the same.
     *
     * @param {rangy.Range} rangy1
     * @param {rangy.Range} rangy2
     * @return {boolean} True if the selection they range represents are equal.
     */
    rangyCompare: function(rangy1, rangy2) {
        return (
            (rangy1.startContainer == rangy2.startContainer) &&
            (rangy1.endContainer == rangy2.endContainer) &&
            (rangy1.startOffset == rangy2.startOffset) &&
            (rangy1.endOffset == rangy2.endOffset)
        );
    },

    /**
     * Disable custom contenteditable features on img.
     *
     * @param {Y.Node} editorNode
     */
    disableContentEditable: function(editorNode) {
        editorNode.all('.atto-image-wrapper').each(function(node) {
            node.getDOMNode().setAttribute('contenteditable', false);
        });

        // Disable IE's custom contenteditable features on img.
        editorNode.all('img').each(function(imgNode) {
            imgNode.getDOMNode().setAttribute('unselectable', 'on');
        });
    },

    /**
     * Gets the mapping of inline style and its value.
     * @param {Y.Node} node
     * @returns {{}} Mapping of all inline style and its value.
     */
    getInlineStyles: function(node) {
        var inlineStyleString = node.getDOMNode().getAttribute('style');

        if (Y.Lang.isString(inlineStyleString) === false || inlineStyleString === '') {
            return {};
        }

        var inlineStyleArray = inlineStyleString.split(';');
        inlineStyleArray = inlineStyleArray.filter(function(style) {
           return style.indexOf(':') !== -1;
        });

        var styleObj = {};
        inlineStyleArray.forEach(function(style) {
            var styleProperty = style.trim().split(':');
            styleObj[styleProperty[0]] = styleProperty[1];
        });

        return styleObj;
    },

    /**
     * Sets the inline style attribute of the given node.
     * @param {Y.Node} node The node to set the inline style of.
     * @param {{}} styleMap The styleMap to set as inline style of the given node.
     */
    setInlineStyles: function(node, styleMap) {
        var inlineStyleArray = [];

        for (var style in styleMap) {
            inlineStyleArray.push(style + ': ' + styleMap[style]);
        }

        node.getDOMNode().setAttribute('style', inlineStyleArray.join('; '));
    },

    /**
     * Remove all the invalid styles of the given node with respect to an array of "valid styles".
     * @param {Y.Node} node The node to set the inline style of.
     * @param {Array} validInlineStyles Valid styles to keep.
     */
    cleanupInlineStyles: function(node, validInlineStyles) {
        var oldInlineStyles = Y.M.atto_image.utility.getInlineStyles(node);
        var newInlineStyles = {};
        for (var style in oldInlineStyles) {
            if (validInlineStyles.indexOf(style) >= 0) {
                newInlineStyles[style] = oldInlineStyles[style];
            }
        }
        Y.M.atto_image.utility.setInlineStyles(node, newInlineStyles);
    },

    /**
     * A function to get the current selected nodes from a given rangy object. This is from Tim Down himself,
     * the guy who created the rangy API.
     * @see http://stackoverflow.com/a/7784176
     *
     * @param {window.rangy} range Rangy object, from the rangy API.
     * @returns {[DOM]} Not necessarily a DOMElement, so check if Y.one returns null.
     */
    getRangeSelectedNodes: function(range) {
        var nextNode = function(node) {
            if (node.hasChildNodes()) {
                return node.firstChild;
            } else {
                while (node && !node.nextSibling) {
                    node = node.parentNode;
                }
                if (!node) {
                    return null;
                }
                return node.nextSibling;
            }
        };

        var node = range.startContainer;
        var endNode = range.endContainer;

        // Special case for a range that is contained within a single node
        if (node == endNode) {
            return [node];
        }

        // Iterate nodes until we hit the end container
        var rangeNodes = [];
        while (node && node != endNode) {
            rangeNodes.push(node = nextNode(node));
        }

        // Add partially selected nodes at the start of the range
        node = range.startContainer;
        while (node && node != range.commonAncestorContainer) {
            rangeNodes.unshift(node);
            node = node.parentNode;
        }

        return rangeNodes;
    }
};