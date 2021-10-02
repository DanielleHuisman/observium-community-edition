 /*
 |-------------------------------------------------------------------
 | jQuery Metal Clone Plugins
 |-------------------------------------------------------------------
 | http://thunderwide.com
 |
 | @category   Plugins
 | @author     Norlihazmey Ghazali <norlihazmey89@thunderwide.com>
 | @license    Licensed under MIT (http://www.opensource.org/licenses/mit-license.php).
 | @copyright  (c) 2015 Norlihazmey(metallurgical)
 | @version    1.3.0
 | @Github     https://github.com/metallurgical/jquery-metal-clone
 |-------------------------------------------------------------------
 */

(function($) {

    $.fn.metalClone = function(options, callback) {

        var opt = cloned = $.extend({}, $.fn.metalClone.defaults, options);
        var base = clonedElement = this;

        return base.each(function(index, elem) {

            // if already defined or register
            // clone plugin inside current selector
            // then no need to redefined it
            var classOrId = $(elem).attr('id') || $(elem).attr('class');

            if (!classOrId) {
                classOrId = elem;
            }

            var generatedReferenceNo = Math.floor(Math.random() * 99999999999 + 1);

            $(elem).data('metalCloneRef', generatedReferenceNo);
            $(elem).addClass('metalElement' + generatedReferenceNo);

            if (undefined == $(elem).data('metalClone-' + classOrId)) {
                $(elem).data('metalClone-' + classOrId, 'metalClone');
            } else {
                return;
            }

            // Get the selector
            // To see either class or ids were used
            var typeSelector = base.selector || '.' + classOrId,
                generatedSelectorClass = '.metalElement' + generatedReferenceNo,
                generatedSelectorClassForRemoveBtn = generatedSelectorClass.replace('.', '') + 'BtnRemove',
                // remove either . or # for class and ID respectively
                newTypeSelector = generatedSelectorClass,
                nodeType = base[0].nodeName,
                // Capture the configuration options
                currentCopyValue = opt.copyValue,
                currentClearExceptions = opt.clearExceptions,
                currentPosition = opt.position,
                currentNumberToClone = opt.numberToClone,
                currentDestination = opt.destination,
                currentIds = opt.ids,
                currentBtnRemoveText = opt.btnRemoveText,
                destinationNodeType = (currentDestination) ? $(currentDestination)[0].nodeName : 'none',
                cloneLimit = opt.cloneLimit,
                cloneLimitText = opt.cloneLimitText,
                cloneLimitClass = opt.cloneLimitClass,
                enableIcon = opt.enableIcon,
                fontAwesomeTheme = opt.fontAwesomeTheme,
                fontAwesomeRemoveClass = opt.fontAwesomeRemoveClass,
                fontAwesomeAddClass = opt.fontAwesomeAddClass,
                fontAwesomeAddDataTransform = opt.fontAwesomeAddDataTransform,
                fontAwesomeAddDataMask = opt.fontAwesomeAddDataMask,
                fontAwesomeRemoveDataTransform = opt.fontAwesomeRemoveDataTransform,
                fontAwesomeRemoveDataMask = opt.fontAwesomeRemoveDataMask,
                enableConfirmMessage = opt.enableConfirmMessage,
                confirmMessageText = opt.confirmMessageText,
                enableAnimation = opt.enableAnimation,
                animationSpeed = opt.animationSpeed,
                enableScrollTop = opt.enableScrollTop,
                scrollTopSpeed = opt.scrollTopSpeed,
                defaultFontAwesomeTheme = ['regular', 'solid', 'brand', 'light', 'basic'],
                onStart = opt.onStart,
                onClone = opt.onClone,
                onComplete = opt.onComplete,
                onClonedRemoved = opt.onClonedRemoved,
                metalRowElementWrapper = 'metalRowElementWrapper',
                // Table list(match with selection)
                allNodeTableWithout = [
                    'TABLE',
                    'TR',
                    'TD',
                    'TBODY',
                    'TFOOT',
                    'THEAD',
                    'TH'
                ],
                element,
                flagClass = false,
                tdCloseParent,
                firstTdChild;

            if (typeSelector.match(/[.]/)) {
                // if the selector is a class,
                // then take the first element only
                flagClass = true;
                element = $(this).first();
            } else {
                // If the selector is an ID
                // return  its object
                element = $(this);
            }
            // if onstart callback was called
            // provide them self paramater
            if ($.isFunction(onStart)) onStart.call(base, base);
            /*=================== parent[table] ===================*/
            // only for table
            if ($.inArray(nodeType, allNodeTableWithout) !== -1) {
                tdCloseParent = element.closest('table');
                firstTdChild = tdCloseParent.find('tr').first();
            }

            /*
            |------------------------------------------------
            | Default clone button
            |------------------------------------------------
            | If user did't not provided the class or id name for
            | cloned button, then system will provided one
            |
            */
            // initialize global variable for clone button
            var currentBtnClone;
            var btnClonePosition;

            // If user not defined clone button,
            // then make new one
            if (opt.btnClone === null) {
                // create new clone button with unique id
                currentBtnClone = "metalBtnClone" + Math.floor(Math.random() * 99999999999 + 1);

                // if selector is a table and destination not table
                if (($.inArray(nodeType, allNodeTableWithout) !== -1) && ($.inArray(destinationNodeType, allNodeTableWithout) == -1)) {
                    btnClonePosition = tdCloseParent;
                }
                // if selector is a table element and destination is a table
                else if (($.inArray(nodeType, allNodeTableWithout) !== -1) && ($.inArray(destinationNodeType, allNodeTableWithout) !== -1)) {
                    btnClonePosition = tdCloseParent;
                }
                // if a selector is not a table && destination not a table
                else if (($.inArray(nodeType, allNodeTableWithout) == -1) && ($.inArray(destinationNodeType, allNodeTableWithout) == -1)) {
                    btnClonePosition = elem;
                }
                // if selector is not a table element and destination is a table
                else if (($.inArray(nodeType, allNodeTableWithout) == -1) && ($.inArray(destinationNodeType, allNodeTableWithout) !== -1)) {
                    btnClonePosition = elem;
                }

                $('<button/>', {
                    type: 'button',
                    text: opt.btnCloneText,
                    class: currentBtnClone + ((opt.btnCloneClass && typeof opt.btnCloneClass !== 'number') ? ' ' + opt.btnCloneClass : ''),
                    css: {
                        marginTop: '10px'
                    }
                }).insertAfter(btnClonePosition);

                // Concat the . sysmbol at beggining of
                // class name for dynamic create button
                currentBtnClone = '.' + currentBtnClone;
            }

            // if user defined the button itself,
            // then use user defined button instead
            else {
                currentBtnClone = opt.btnClone;
            }

            if (enableIcon) {
                var iconContent = '<i class="' + getFontAwesomeThemeType() + ' ' + fontAwesomeAddClass + '" data-fa-transform="' + fontAwesomeAddDataTransform + '" data-fa-mask="' + fontAwesomeAddDataMask + '"></i> ';
                $(currentBtnClone).get(0).insertAdjacentHTML('afterbegin', iconContent);
            }

            $(currentBtnClone).data('metalCloneButtonRef', generatedReferenceNo);

            $(document).on({
                mouseenter: function() {

                    $(this).find('.operations').css({
                        display: 'block'
                    });

                },
                mouseleave: function() {
                    $(this).find('.operations').css({
                        display: 'none'
                    });
                }
            }, generatedSelectorClass);

            /*
            |-------------------------------------------------
            | When Clone button was clicked
            |-------------------------------------------------
            */
            $(document).on('click', currentBtnClone, function() {

                // Store the destination of cloned element
                var $that = $(this);
                var destinationClone;
                var toClone = "";

                // immedietly invoked function
                // if cancelClone & removeCloned
                // function was called
                // then register new window properties for its
                // unique selector name with true value
                // later on we check this value to
                // do something depend on it
                (function(newTypeSelector) {
                    opt.cancelClone = function(flag) {
                        if (flag) window[newTypeSelector + 'cancelClone'] = flag;
                    };
                    opt.removeCloned = function(flag) {
                        if (flag) window[newTypeSelector + 'removeCloned'] = flag;
                    };
                })(generatedSelectorClass);

                // onClone callback accept 2 paramaters
                // param1 - current cloned
                // param2 - current object
                if ($.isFunction(onClone)) onClone.call(base, base, cloned);

                // checked for window variable
                // if exist never proceed
                // this for stopping cloned process
                if (window[generatedSelectorClass + 'cancelClone'] && typeof window[generatedSelectorClass + 'cancelClone'] !== undefined) {

                    delete window[generatedSelectorClass + 'cancelClone'];
                    return;
                }

                // If destination provided,
                // then use user defined destination
                if (currentDestination !== false) {

                    // Use user defined destination
                    destinationClone = $(currentDestination);

                    // Put either after or before depend
                    // on user defined position
                    if (currentPosition === "after") {
                        toClone = loopCloneAppendPrepend(currentNumberToClone, element, destinationClone, currentPosition);
                    } else {
                        toClone = loopCloneAppendPrepend(currentNumberToClone, element, destinationClone, currentPosition);
                    }
                }
                // If did't provied,just clone element
                // after/before cloned element
                else {
                    destinationClone = $(generatedSelectorClass);

                    if (currentPosition === "after") {
                        toClone = loopCloneAfterBefore(currentNumberToClone, element, destinationClone.last(), currentPosition);
                    } else {
                        toClone = loopCloneAfterBefore(currentNumberToClone, element, destinationClone.first(), currentPosition);
                    }
                }

                // trigger onComplete callback if
                // user defined it
                // base --> current context
                // clonedElement --> the element that want to clone
                // cloned --> plugin itself
                // toClone --> cloned element that being cloned
                if ($.isFunction(onComplete)) onComplete.call(base, $(elem), cloned, toClone);

                // if user want to remove cloned element
                // which is set to TRUE, then we remove
                // cloned element
                if (window[generatedSelectorClass + 'removeCloned'] && typeof window[generatedSelectorClass + 'removeCloned'] !== undefined) {

                    delete window[generatedSelectorClass + 'removeCloned'];
                    $(toClone).remove();

                    // onClonedRemoved callback accept 1 paramater
                    // param1 - removed element
                    if ($.isFunction(onClonedRemoved)) onClonedRemoved.call(base, toClone);
                }

                return;
            });

            /**
             * Get script path.
             *
             * @return {path}
             */
            function scriptPath() {

                var scripts = $('script'),
                    path = '';

                if (scripts && scripts.length > 0) {
                    for (var i in scripts) {

                        var regex = /jquery.metalClone*/g;
                        var regexRep = /jquery.metalClone.js|jquery.metalClone.min.js/g;

                        if (scripts[i].src && scripts[i].src.match(regex)) {

                            path = scripts[i].src.replace(regexRep, '');
                            break;
                        }
                    }
                }
                return path;
            }

            /*
            |----------------------------------------------------
            | Function to clone element(IF destination provided)
            |----------------------------------------------------
            | numberToClone     : Number of container to clone
            | elementClone      : Element want to clone
            | destination       : Placeholder/destination to place the cloned element
            | position          : Position of newly cloned element
            |
            */
            function loopCloneAppendPrepend(numberToClone, elementClone, destination, position) {

                // Cache the clone obj
                var cloneObj = elementClone,
                    check,
                    toClone = "",
                    finalClonedElement = '',
                    clonedElement = [];

                // If user put 0,
                // Then assign 1 as a default value
                // else use the provided value
                numberToClone = (numberToClone == 0) ? 1 : numberToClone;

                // table element but destination only table
                // if selection is a table && destination a table
                if (($.inArray(nodeType, allNodeTableWithout) !== -1) && ($.inArray(destinationNodeType, allNodeTableWithout) !== -1)) {
                    for (var i = 0; i < numberToClone; i++) {
                        check = limitHandler();
                        if (check) return;

                        toClone = cloneObj.clone();

                        if (enableAnimation) {

                            toClone.children('td').wrapInner('<div class="' + metalRowElementWrapper + '"></div>');
                            toClone.children('td').each(function(index, element) {
                                $(element).children('div').slideDown();
                            });

                        }

                        if (position === 'after') {
                            toClone.insertAfter(destination.find('tr').last());
                        } else {
                            toClone.insertAfter(destination.find('tr').first());
                        }

                        if (enableAnimation) {

                            var length = toClone.children('td').length;
                            var currentIteration = 0;

                            toClone.children('td').children('div').hide().slideDown(function() {
                                toClone.children('td').children('.' + metalRowElementWrapper).contents().unwrap();
                                toClone.find('td').last().append(getRemoveButtonStructure('table'));

                                if (!currentCopyValue) {
                                    clearForm(toClone);
                                }

                                (function (index) {
                                    if (enableScrollTop) {
                                        if (index == 1 && currentIteration == (length - 1)) {
                                            $(document).triggerHandler('metal-event:scrollTop', {
                                                cloned_element: toClone,
                                                scroll_top_speed: scrollTopSpeed
                                            });
                                        }
                                    }
                                })(i);

                                currentIteration++;

                            });
                        } else {

                            toClone.find('td').last().append(getRemoveButtonStructure('table'));

                            (function (index) {
                                if (enableScrollTop) {
                                    if (index == 0) {
                                        $(document).triggerHandler('metal-event:scrollTop', {
                                            cloned_element: toClone,
                                            scroll_top_speed: scrollTopSpeed
                                        });
                                    }
                                }
                            })(i);

                            if (!currentCopyValue) {
                                clearForm(toClone);
                            }

                        }

                        clonedElement.push(toClone);
                    }
                }
                // not table element and destination not table element
                // if selection is not a table && destination not a table element
                else if (($.inArray(nodeType, allNodeTableWithout) == -1) && ($.inArray(destinationNodeType, allNodeTableWithout) == -1)) {
                    for (var i = 0; i < numberToClone; i++) {
                        check = limitHandler();
                        if (check) return;

                        toClone = cloneObj.clone();

                        if (enableAnimation) {
                            if (position === 'after') {
                                destination.append(toClone.append(getRemoveButtonStructure('div')));
                                destination.children().last().hide().slideDown(animationSpeed);
                            } else {
                                destination.prepend(toClone.append(getRemoveButtonStructure('div')));
                                destination.children().first().hide().slideDown(animationSpeed);
                            }

                        } else {
                            destination.prepend(toClone.append(getRemoveButtonStructure('div')));
                        }

                        if (enableScrollTop) {
                            (function (index) {
                                if (index == 0) {
                                    $(document).triggerHandler('metal-event:scrollTop', {
                                        cloned_element: toClone,
                                        scroll_top_speed: scrollTopSpeed
                                    });
                                }
                            })(i);
                        }

                        if (currentCopyValue) { /* never copy */ } else {
                            clearForm(toClone);
                        }

                        clonedElement.push(toClone);
                    }
                }
                // If selection is not a table && destination is a table.
                else if (($.inArray(nodeType, allNodeTableWithout) == -1) && ($.inArray(destinationNodeType, allNodeTableWithout) !== -1)) {
                    for (var i = 0; i < numberToClone; i++) {
                        check = limitHandler();
                        if (check) return;

                        toClone = cloneObj.clone();

                        if (enableAnimation) {
                            if (position === 'after') {
                                destination.append(toClone.append(getRemoveButtonStructure('div')));
                                destination.children().last().hide().slideDown(animationSpeed);
                            } else {
                                destination.prepend(toClone.append(getRemoveButtonStructure('div')));
                                destination.children().first().hide().slideDown(animationSpeed);
                            }

                        } else {
                            if (position === 'after') {
                                destination.append(toClone.append(getRemoveButtonStructure('div')));

                            } else {
                                destination.prepend(toClone.append(getRemoveButtonStructure('div')));
                            }
                        }

                        (function (index) {
                            if (enableScrollTop) {
                                if (index == 0) {
                                    $(document).triggerHandler('metal-event:scrollTop', {
                                        cloned_element: toClone,
                                        scroll_top_speed: scrollTopSpeed
                                    });
                                }
                            }
                        })(i);

                        if (!currentCopyValue) {
                            clearForm(toClone);
                        }

                        clonedElement.push(toClone);
                    }

                }

                // Table element but destination not table element.
                else if (($.inArray(nodeType, allNodeTableWithout) !== -1) && ($.inArray(destinationNodeType, allNodeTableWithout) == -1)) {
                    console.error('MetalClone Error: Destination provided is not table element. Table rows should exist inside table element');
                    return false;
                }

                // If the opt.ids is an empty array
                // Is a default value
                if ($.isArray(currentIds) && $.isEmptyObject(currentIds)) {

                    finalClonedElement = $.map(clonedElement, function(e, i) {
                        return $(e).get(0);
                    });
                }

                // If user provided element in array container
                // Then call the function
                // pass the opt.ids array value[* or a few]
                else if ($.isArray(currentIds) && !$.isEmptyObject(currentIds)) {

                    // call the function
                    finalClonedElement = idIncreament(currentIds);
                }

                return finalClonedElement;
            }

            /*
            |--------------------------------------------------------
            | Function to clone element(IF destination not provided)
            |--------------------------------------------------------
            | numberToClone     : Number of container to clone
            | elementClone      : Element want to clone
            | destination       : Placeholder/destination to place the cloned element
            | position          : Position of newly cloned element
            |
            */
            function loopCloneAfterBefore(numberToClone, elementClone, destination, position) {

                var check,
                    // Cache the clone obj
                    cloneObj = elementClone,
                    toClone = "",
                    finalClonedElement = '',
                    clonedElement = [];

                // If user put 0,
                // Then assign 1 as a default value
                // else use the provided value
                numberToClone = (numberToClone == 0) ? 1 : numberToClone;

                // Clone element[insert after the clone element]
                // selection is a table
                if (($.inArray(nodeType, allNodeTableWithout) !== -1)) {

                    for (var i = 0; i < numberToClone; i++) {

                        check = limitHandler();
                        if (check) return;

                        toClone = cloneObj.clone();

                        if (enableAnimation) {
                            toClone.children('td').wrapInner('<div class="' + metalRowElementWrapper + '"></div>');
                            toClone.children('td').each(function(index, element) {
                                $(element).children('div').slideDown();
                            });
                        }

                        if (position === 'after') {
                            toClone.insertAfter(destination);
                        } else {
                            toClone.insertBefore(destination);
                        }

                        if (enableAnimation) {

                            var length = toClone.children('td').length;
                            var currentIteration = 0;

                            toClone.children('td').children('div').hide().slideDown(function() {

                                toClone.children('td').children('.' + metalRowElementWrapper).contents().unwrap();
                                toClone.find('td').last().append(getRemoveButtonStructure('table'));

                                if (!currentCopyValue) {
                                    clearForm(toClone);
                                }

                                (function (index) {
                                    if (enableScrollTop) {
                                        if (index == 1 && currentIteration == (length - 1)) {
                                            $(document).triggerHandler('metal-event:scrollTop', {
                                                cloned_element: toClone,
                                                scroll_top_speed: scrollTopSpeed
                                            });
                                        }
                                    }
                                })(i);

                                currentIteration++;
                            });

                        } else {
                            toClone.find('td').last().append(getRemoveButtonStructure('table'));

                            (function (index) {
                                if (enableScrollTop) {
                                    if (index == 0) {
                                        $(document).triggerHandler('metal-event:scrollTop', {
                                            cloned_element: toClone,
                                            scroll_top_speed: scrollTopSpeed
                                        });
                                    }
                                }
                            })(i);

                            if (!currentCopyValue) {
                                clearForm(toClone);
                            }

                        }

                        clonedElement.push(toClone);
                    }
                }

                // If selection is not a table
                else if (($.inArray(nodeType, allNodeTableWithout) == -1)) {

                    for (var i = 0; i < numberToClone; i++) {

                        var toCloneElement;

                        check = limitHandler();
                        if (check) return;

                        toClone = cloneObj.clone();

                        if (position === 'after') {
                            toClone.insertAfter(destination);
                        } else {
                            toClone.insertBefore(destination);
                        }

                        if (enableAnimation) {

                            (function (index) {
                                toClone.hide().slideDown(animationSpeed, function () {
                                    if (enableScrollTop) {
                                        if (index == 0) {
                                            $(document).triggerHandler('metal-event:scrollTop', {
                                                cloned_element: toClone,
                                                scroll_top_speed: scrollTopSpeed
                                            });
                                        }
                                    }
                                }).append(getRemoveButtonStructure('div'));
                            })(i);

                        } else {
                            toClone.append(getRemoveButtonStructure('div'));
                            (function (index) {
                                if (enableScrollTop) {
                                    if (index == 0) {
                                        $(document).triggerHandler('metal-event:scrollTop', {
                                            cloned_element: toClone,
                                            scroll_top_speed: scrollTopSpeed
                                        });
                                    }
                                }
                            })(i);
                        }

                        if (!currentCopyValue) {
                            clearForm(toClone);
                        }

                        clonedElement.push(toClone);
                    }
                }

                // If the opt.ids is an empty array
                // is a default value
                if ($.isArray(currentIds) && $.isEmptyObject(currentIds)) {
                    finalClonedElement = $.map(clonedElement, function(e, i) {
                        return $(e).get(0);
                    });
                }

                // If user provided element in array container
                // Then call the function
                // pass the opt.ids array value[* or a few]
                else if ($.isArray(currentIds) && !$.isEmptyObject(currentIds)) {
                    // call the function
                    finalClonedElement = idIncreament(currentIds);
                }

                return finalClonedElement;
            }

            /**
             * Set form fields to be empty.
             *
             * @param container
             *
             * @return void
             */
            function clearForm(container) {

                container.find('input:not("input[type=button], input[type=submit], input[type=checkbox], input[type=radio]"), textarea, select').each(function() {
                    $ (this).not(currentClearExceptions).val('');
                });
            }

            /**
             * Get remove button element.
             *
             * @param type Either "table" or "else"
             * @returns DOMElement
             */
            function getRemoveButtonStructure(type) {

                var iconContent = '';
                var output = '';

                if (enableIcon) {
                    iconContent = '<i class="' + getFontAwesomeThemeType() + ' ' + fontAwesomeRemoveClass + '" data-fa-transform="' + fontAwesomeRemoveDataTransform + '" data-fa-mask="' + fontAwesomeRemoveDataMask + '"></i> ';
                }

                switch (type) {
                    case 'table':
                        output = '<div class="operation-container"><div class="operations"><div class="' + generatedSelectorClassForRemoveBtn + ' metal-btn-remove operationsImg" data-metal-ref="' + generatedSelectorClass + '">' + iconContent + '<span>' + currentBtnRemoveText + '</span></div></div></div>';
                        break;
                    default:
                        // Changed by Observium Developers:
                        // removed: (style="margin-top: 20px")
                        // added: btnRemoveId
                        output = '<button type="button"' + ((opt.btnRemoveId && typeof opt.btnRemoveId === 'string') ? ' id="' + opt.btnRemoveId + '"' : '') + ' class="' + generatedSelectorClassForRemoveBtn + ' metal-btn-remove ' + ((opt.btnRemoveClass && typeof opt.btnRemoveClass !== 'number') ? opt.btnRemoveClass : '') + '" data-metal-ref="' + generatedSelectorClass + '">' + iconContent + currentBtnRemoveText + '</button>';
                }

                return output;
            }

            /**
             * Get font awesome theme.
             *
             * @returns {string}
             */
            function getFontAwesomeThemeType()
            {
                if ($.inArray(fontAwesomeTheme, defaultFontAwesomeTheme) === -1) {
                    return 'far';
                }

                var theme;

                switch (fontAwesomeTheme) {
                    case 'solid':
                        theme = 'fas';
                        break;
                    case 'brand':
                        theme = 'fab';
                        break;
                    case 'light':
                        theme = 'fal';
                        break;
                    case 'basic':
                        theme = 'fa';
                        break;
                    default:
                        theme = 'far';
                }

                return theme;
            }

            /**
             * Increment an ID to be unique.
             *
             * @param arr
             * @returns {Array}
             */
            function idIncreament(arr) {

                var ids_value, clonedElement = [];

                // Check if the paramter passed
                // has *(all) symbol
                // if yes, then find all element
                if ($.inArray('*', arr)) {
                    ids_value = '*';
                }

                // find element provided in array
                //  list only
                else {
                    ids_value = arr.join(',');
                }

                // iterate throught cloned container
                $(generatedSelectorClass).not(':first').each(function(inc, e) {

                    // then find the element either * or a few
                    // depend on user defined and default value
                    $(this).find(ids_value).each(function(i, ee) {

                        // looking for and ID(S)
                        // if found, then increament its value
                        // to ensure all the same clone element
                        // have unique id value
                        if ($(this).attr('id')) {

                            // Get the original value
                            var oldValue = $(this).attr('id');
                            var newValue = oldValue.replace(/\d+/g, '');

                            // Set the new id(s) value
                            $(this).attr('id', newValue + parseInt(inc));
                        }
                    });

                    clonedElement.push($(this).get(0));

                });

                return clonedElement;
            }

            /**
             * Check no of element was cloned. This function for limit
             * @returns {*}
             */
            function checkLimit() {

                var numberOfCloneElementExisted;

                // if the selector is a class
                // then no problem, just get length
                // of all the element with same class existed
                // if (flagClass)
                //     numberOfCloneElementExisted = $(generatedSelectorClass).length;
                // if the selector is an IDs
                // then, find all the element with
                // same id with same name
                // else
                //     numberOfCloneElementExisted = $('[id="' + base[0].id + '"]').length;
                numberOfCloneElementExisted = $(generatedSelectorClass).length;

                // if the clone limit option provided by users
                // and the input is a number
                if (cloneLimit != "infinity" && typeof cloneLimit == "number") {

                    // if number of clone element more than limit provided
                    // return false(not possible to clone element exceed limit)
                    // then return false
                    if (currentNumberToClone > cloneLimit) {
                        return false;
                        // if meet the condition, then return the length
                        // of clone element existed
                    } else {
                        return numberOfCloneElementExisted;
                    }
                }

                // user not provided the limit
                // then just use the default value
                // default value is infinity
                else {
                    return 'no limit';
                }
            }

            /**
             * Get selector name being select to clone.
             * @returns {string}
             */
            function getSelectorName() {
                var name;

                if (flagClass)
                    name = generatedSelectorClass.replace('.', '');
                else
                    name = generatedSelectorClass.replace('#', '');

                return name;
            }

            /**
             * Check the cloned element meet the condition or not.
             * @returns {boolean}
             */
            function limitHandler() {

                // get length of cloned element
                var flagLimit = checkLimit(),
                    // store length
                    canProceed,
                    // flag bool value
                    flagProceed = false;

                // if number to clone more than limit
                // return to true
                if (!flagLimit) {
                    console.error('MetalClone Error: numberToClone option defined is more than cloneLimit option');
                    alert('MetalClone Error: numberToClone option defined is more than cloneLimit option');

                    flagProceed = true;
                }

                // if no limit, then assign 0 value as a default
                // otherwise use the current length
                else {
                    canProceed = (flagLimit == "no limit") ? 0 : flagLimit;
                }

                // if cloned element already exceed limit provided
                // stop current process
                if (canProceed > cloneLimit) {
                    console.log("Can't clone more than limit provided");
                    if ($(currentBtnClone).next().is('span')) {
                        $(currentBtnClone).next().html(cloneLimitText);
                    } else {

                        // call function to get selector name
                        // without .(class) or #(id) symbols
                        var selectorName = getSelectorName();

                        // create span element for error_limit message
                        // after clone button
                        $('<span/>', {
                            'data-clone-reference': selectorName,
                            class: 'metal-error-limit' + ((cloneLimitClass && typeof cloneLimitClass !== 'number') ? ' ' + cloneLimitClass : ''),
                            text: cloneLimitText
                        }).insertAfter(currentBtnClone);
                    }

                    flagProceed = true;
                }

                return flagProceed;
            }

            /*
            |-------------------------------------------------
            | When Remove button was clicked
            |-------------------------------------------------
            */
            $(document).on('click', 'button.' + generatedSelectorClassForRemoveBtn + ',div.' + generatedSelectorClassForRemoveBtn, function() {

                var
                    selectorName,
                    parentToRemove,
                    className = $(this).data('metalRef').replace('.', ''),
                    parentTr = $(this).closest('tr.' + className);

                if (typeof enableConfirmMessage === 'boolean' && enableConfirmMessage) {
                    if (!confirm(confirmMessageText)) {
                        return false;
                    }
                }

                // without .(class) or #(id) symbols
                selectorName = getSelectorName();

                // call function to get selector name
                if (parentTr.length) {
                    if (opt.enableAnimation) {
                        parentTr.children('td').wrapInner('<div></div>');
                        parentTr.children('td').children('div').each(function (index, elem) {
                            $(elem).slideUp(animationSpeed, function() {
                                if (index === (parentTr.children('td').children('div').length - 1)) {
                                    $(document).triggerHandler('metal-event:onClonedRemoved', {
                                        base: base,
                                        callback: onClonedRemoved,
                                        toRemoveElement: parentTr
                                    });
                                }
                            });
                        });
                    } else {
                        $(document).triggerHandler('metal-event:onClonedRemoved', {
                            base: base,
                            callback: onClonedRemoved,
                            toRemoveElement: parentTr
                        });
                    }
                } else {
                    if (opt.enableAnimation) {
                        $(this).closest(generatedSelectorClass).slideUp(animationSpeed, function () {

                            // onClonedRemoved callback accept 1 paramater
                            // param1 - removed element
                            $(document).triggerHandler('metal-event:onClonedRemoved', {
                                base: base,
                                callback: onClonedRemoved,
                                toRemoveElement: $(this).closest(generatedSelectorClass)
                            });
                        });
                    } else {
                        $(document).triggerHandler('metal-event:onClonedRemoved', {
                            base: base,
                            callback: onClonedRemoved,
                            toRemoveElement: $(this).closest(generatedSelectorClass)
                        });
                    }
                }

                // remove error_limit message after remove
                // current deleted element
                $('body').find('[data-clone-reference="' + selectorName + '"]').remove();
            });
        });
    };

    $.fn.metalClone.defaults = {

        destination: false, // Put your selector(parent container) eg : .myContainer | #myContainer
        position: 'after', // Available in two option : after & before
        numberToClone: 1, // Number of element to clone
        ids: [], // Element to increase the id(s) value, unique purpose
        // eg : ['input','select','textarea']
        // Available options :
        // - input
        // - select
        // - textarea
        // - div
        // - span
        // - i
        // - strong
        // - h1-h6
        // * -> find all element inside container

        btnClone: null, // Put your selector(button class or id name) eg : .clickMe | #clickMe
        copyValue: false, // Clone together the previous element value - available for form element only
        clearExceptions: '', // Make exception for elements that you do not want to clear: eg: '.my_exception_element, #my_exception_element'
        btnRemoveText: 'Remove', // Text appear on remove button
        btnRemoveClass: null, // Adding user defined class name for remove button
        btnRemoveId: null, // Adding user defined id for remove button
        btnCloneText: 'Create New Element', // Text appear on clone button
        btnCloneClass: null, // Adding user defined class name for clone button
        cloneLimit: 'infinity', // limit the element that want to clone,
        cloneLimitText: 'Clone limit reached',
        cloneLimitClass: null, // Adding user defined class name for clone limit message
        onStart: null, // on start plugin initialization
        onClone: null, // on cloned element(when cloned button clicked)
        onComplete: null, // on success/complete cloned element render into page
        onClonedRemoved: null, // on delete/remove cloned element,
        enableIcon: true, // Set to false to hide button's icon
        // These sections only available when "enableIcon" set to true"
        fontAwesomeTheme: 'solid', // Available option "regular", "solid", "brand", "light", "basic"
        fontAwesomeRemoveClass: 'fa-trash-alt', // Reference: https://fontawesome.com/icons?d=gallery
        fontAwesomeAddClass: 'fa-plus', // Reference: https://fontawesome.com/icons?d=gallery
        fontAwesomeAddDataTransform: '', // Reference: https://fontawesome.com/how-to-use/svg-with-js
        fontAwesomeAddDataMask: '', // Reference: https://fontawesome.com/how-to-use/svg-with-js
        fontAwesomeRemoveDataTransform: '', // Reference: https://fontawesome.com/how-to-use/svg-with-js
        fontAwesomeRemoveDataMask: '', // Reference: https://fontawesome.com/how-to-use/svg-with-js
        enableConfirmMessage: true, // Set to false to disable confirmation message when remove action triggered
        confirmMessageText: 'Are you sure?', // Set your custom message[only available when enableConfirmMessage is set to true]
        enableAnimation: true, // Set to false to disable animation on clone and remove element
        animationSpeed: 400, // Duration speed in milliseconds,
        enableScrollTop: true, // Set to false to disable page from scrolling to newly created element
        scrollTopSpeed: 1000, // Default speed scroll top
        // Please wait for more callback option.. coming soon..
    };

    /**
     * [Event listener] Triggered when remove cloned element.
     */
    $(document).on('metal-event:onClonedRemoved', function (event, data) {
        data.toRemoveElement.remove();
        if ($.isFunction(data.callback)) data.callback.call(data.base, data.toRemoveElement);
    });

    /**
     * [Event Listener] Triggered when "enableScrollTop" option is enable.
     */
    $(document).on('metal-event:scrollTop', function (event, data) {
        $('html,body').animate({
            scrollTop: $(data.cloned_element).offset().top
        }, data.scroll_top_speed);
    });

})(jQuery);
