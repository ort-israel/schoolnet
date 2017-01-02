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
 * @copyright  2016 Joey Andres <jandres@ualberta.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * MutationObserver is cool and all, but there are limits. For instance, we can't
 * watch the Y.Node itself, but only its children. Since watching the node AND node's children
 * is a common pattern for resize/crop/rotate, this module is built.
 *
 *     var crazyNode = Y.one('#foo');
 *     var options = {
 *       node: crazyNode,
 *       deletionCallback: function(removedNodes) {
 *         console.log('crazyNode or it's children have been deleted.');
 *       },
 *
 *       // Usual MutationObserver arguments from down here.
 *       childList: true,  // True by default. Ovverride to false if you want.
 *       subtree: true  // True by default. Override to false if you want.
 *     };
 *     var resizable = new Y.M.atto_image.resizable(node);
 *
 * @class Y.M.atto_image.DeleteMutationObserver
 */
Y.M.atto_image.DeleteMutationObserver = function() {
    Y.M.atto_image.DeleteMutationObserver.superclass.constructor.apply(this, arguments);
};
Y.extend(Y.M.atto_image.DeleteMutationObserver, Y.Base, {
    /**
     * The node being watched.
     *
     * @property node
     * @type {null|Y.Node}
     * @required
     * @default null
     * @writeOnce
     * @public
     */
    node: null,

    /**
     * The mutation observer that observe's this.node's mutation.
     *
     * @property _mutationObserver
     * @type {null|MutationObserver}
     * @default null
     * @private
     */
    _mutationObserver: null,

    /**
     * The mutation observer that observe's this.node's children's mutation.
     *
     * @property _childrenMutationObserver
     * @type {null|MutationObserver}
     * @default null
     * @private
     */
    _childrenMutationObserver: null,

    /**
     * @property _mutationObserverConfig
     * @type {Object}
     * @default {childList: true, subtree: true}
     * @private
     */
    _mutationObserverConfig: {childList: true, subtree: true},

    /**
     * @property _deletionCallback
     * @type {Function|null}
     * @default null
     * @private
     */
    _deletionCallback: null,

    /**
     * When a div with many children is deleted, multiple calls to _deletionCallback might be called. What if
     * the developer calls Y.M.atto_image.DeletionMutationObserver.stop() in first callback?
     * The remaining _deletionCallback (for other deleted DOM elements) are still going to be called.
     * To avoid this, this.stop() will set this._start flag to false thus avoiding further calls to _deletionCallback.
     *
     * @property _start
     * @type {Boolean}
     * @default false
     * @private
     */
    _start: false,

    initializer: function(cfg) {
        this.node = cfg.node;
        this._deletionCallback = cfg.deletionCallback;

        delete cfg.node;
        delete cfg.deletionCallback;
        // @see http://yuilibrary.com/yui/docs/api/classes/YUI.html#method_merge
        // "The properties from later objects will overwrite those in earlier objects."
        this._mutationObserverConfig = Y.merge(this._mutationObserverConfig, cfg);

        Y.log(
            'initialized',
            'debug',
            Y.M.atto_image.DeleteMutationObserver.NAME
        );
    },

    destructor: function() {
        this.detachAll();
        this.stop();
    },

    /**
     * Starts the MutationObserver instances.
     */
    start: function() {
        this._start = true;

        this._childrenMutationObserver = new MutationObserver(function(mutations) {
            var nodeNotSet = !this.node;

            // For now, to reduce error, just throw a debug warning when node not set so we reduce error rate. Wait
            // until developer sets the Y.M.atto_image.DeleteMutationObserver.node
            if (nodeNotSet) {
                Y.log(
                    'this.node not set causing problem in start method',
                    'debug',
                    Y.M.atto_image.DeleteMutationObserver.NAME
                );
                return;
            }

            mutations.forEach(function(mutation) {
                var deletionOccurred = mutation.removedNodes.length > 0;
                if (deletionOccurred && this._start) {
                    Y.log(
                        "node deletion occurred in node's children",
                        'debug',
                        Y.M.atto_image.DeleteMutationObserver.NAME
                    );
                    if (this._deletionCallback) {
                        this._deletionCallback(mutation.removedNodes);
                    }
                }
            }.bind(this));
        }.bind(this));
        this._childrenMutationObserver.observe(this.node.getDOMNode(), this._mutationObserverConfig);

        this._mutationObserver = new MutationObserver(function(mutations) {
            var nodeNotSet = !this.node;

            // For now, to reduce error, just throw a debug warning when node not set so we reduce error rate. Wait
            // until developer sets the Y.M.atto_image.DeleteMutationObserver.node
            if (nodeNotSet) {
                Y.log(
                    'this.node not set causing problem in start method',
                    'debug',
                    Y.M.atto_image.DeleteMutationObserver.NAME
                );
                return;
            }

            mutations.forEach(function(mutation) {
                var deletionOccurred = mutation.removedNodes.length > 0;
                if (deletionOccurred && this._start) {
                    if ([].indexOf.call(mutation.removedNodes, this.node.getDOMNode()) >= 0) {
                        Y.log(
                            'delete-mutation-observer',
                            'debug',
                            Y.M.atto_image.DeleteMutationObserver.NAME
                        );
                        if (this._deletionCallback) {
                            this._deletionCallback(mutation.removedNodes);
                        }
                    }
                }
            }.bind(this));
        }.bind(this));
        this._mutationObserver.observe(this.node.ancestor().getDOMNode(), this._mutationObserverConfig);
    },

    /**
     * Stops MutationObserver instances.
     */
    stop: function() {
        this._start = false;

        if (this._mutationObserver) {
            this._mutationObserver.disconnect();
            this._mutationObserver = null;
        }

        if (this._childrenMutationObserver) {
            this._childrenMutationObserver.disconnect();
            this._childrenMutationObserver = null;
        }
    }
}, {
    NAME: 'atto_image_DeleteMutationObserver',

    /**
     * See if DeleteMutationObserver is supported.
     *
     * @return {Boolean} true if supported in browser, false otherwise.
     */
    isSupported: function() {
        var supported = Y.Lang.isObject(window.MutationObserver);
        Y.log(
            supported ? 'supported' : 'not supported',
            'debug',
            Y.M.atto_image.DeleteMutationObserver.NAME
        );
        return supported;
    }
});